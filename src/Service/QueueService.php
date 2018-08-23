<?php
namespace SixMQ\Service;

use Imi\Config;
use Imi\ConnectContext;
use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use SixMQ\Struct\Queue\Message;
use SixMQ\Util\QueueCollection;
use SixMQ\Struct\Queue\Server\Pop;
use SixMQ\Struct\Queue\Server\Reply;
use Imi\Util\CoroutineChannelManager;
use SixMQ\Struct\Queue\Server\Push;
use Imi\RequestContext;
use SixMQ\Util\QueuePushBlockParser;
use Imi\ServerManage;

abstract class QueueService
{
	/**
	 * 消息入队列
	 *
	 * @param \SixMQ\Struct\Queue\Client\Push $data
	 * @return \SixMQ\Struct\Queue\Server\Reply|null
	 */
	public static function push($data)
	{
		$messageId = null;
		$result = PoolManager::use('redis', function($resource, $redis) use($data, &$messageId){
			// 生成消息ID
			$messageId = RedisKey::getMessageId();
			// 开启事务
			$redis->multi();
			// 保存消息
			$message = new Message($data->data, $messageId);
			$message->queueId = $data->queueId;
			$redis->set($messageId, $message);
			// 加入消息队列
			$redis->rpush(RedisKey::getMessageQueue($data->queueId), $messageId);
			// 运行事务
			return $redis->exec();
		});
		$success = null !== $result;
		$return = new Push($success);
		$return->queueId = $data->queueId;
		$return->messageId = $messageId;
		if($success && 0 !== $data->block)
		{
			$fd = RequestContext::get('fd');
			// 加入到 push block 中
			QueuePushBlockParser::add($fd, $data, $return);
			$return = null;
		}
		go(function() use($success, $data){
			// 队列记录
			if($success && !QueueCollection::has($data->queueId))
			{
				QueueCollection::append($data->queueId);
			}
			// 阻塞请求支持
			if(CoroutineChannelManager::stats('PopBlockQueue')['consumer_num'] > 0)
			{
				CoroutineChannelManager::push('PopBlockQueue', true);
			}
		});
		return $return;
	}

	/**
	 * 消息出队列
	 *
	 * @param \SixMQ\Struct\Queue\Client\Pop $data
	 * @return \SixMQ\Struct\Queue\Server\Pop
	 */
	public static function pop($data)
	{
		$result = static::tryPop($data, $messageId, $message);

		if(!$result && 0 !== $data->block)
		{
			if($data->block > 0)
			{
				$maxWaitTime = min(Config::get('@app.common.queue_block_time'), $data->block);
			}
			else
			{
				$maxWaitTime = PHP_INT_MAX;
			}
			$beginTime = microtime(true);
			// 没有可弹出的消息，并且是阻塞请求
			do{
				if(!CoroutineChannelManager::pop('PopBlockQueue', $maxWaitTime - (microtime(true) - $beginTime)))
				{
					break;
				}
			}while(($connectContextExists = ConnectContext::exsits()) && !($result = static::tryPop($data, $messageId, $message)));
			// 阻塞请求支持，如果当前是连接断开，把阻塞请求让给别的连接处理
			if(!$connectContextExists && CoroutineChannelManager::stats('PopBlockQueue')['consumer_num'] > 0)
			{
				if(CoroutineChannelManager::stats('PopBlockQueue')['consumer_num'] > 0)
				{
					CoroutineChannelManager::push('PopBlockQueue', true);
				}
			}
		}
		$return = new Pop($result);
		if($result)
		{
			$return->queueId = $data->queueId;
			$return->messageId = $messageId;
			$return->data = $message;
		}
		return $return;
	}

	/**
	 * 尝试消息出队列
	 *
	 * @param \SixMQ\Struct\Queue\Client\Pop $data
	 * @param string $messageId
	 * @param string $message
	 * @return boolean
	 */
	private static function tryPop($data, &$messageId, &$message)
	{
		return PoolManager::use('redis', function($resource, $redis) use($data, &$messageId, &$message){
			// 取出消息ID
			$messageId = $redis->lpop(RedisKey::getMessageQueue($data->queueId));
			if(null === $messageId)
			{
				return false;
			}
			// 消息处理最大超时时间
			$expireTime = microtime(true) + $data->maxExpire;
			// 加入工作集合
			$redis->zadd(RedisKey::getWorkingMessageSet($data->queueId), $expireTime, $messageId);
			// 取出消息
			$message = $redis->get($messageId);
			return true;
		});
	}

	/**
	 * 消息超时处理
	 *
	 * @param string $queueId
	 * @param string $messageId
	 * @return void
	 */
	public static function expireMessage($queueId, $messageId)
	{
		PoolManager::use('redis', function($source, $redis) use($queueId, $messageId){
			$workingMessageSetKey = RedisKey::getWorkingMessageSet($queueId);

			// 消息执行超时
			$message = $redis->get($messageId);

			// 移出工作集合
			$redis->zrem($workingMessageSetKey, $messageId);

			$message->success = false;
			$message->resultData = 'timeout';

			// 加入队列
			$redis->rpush(RedisKey::getMessageQueue($queueId), $messageId);
			$message->inTime = time();
			$message->consum = false;

			// 设置消息数据
			$redis->set($messageId, $message);
		});
		// 处理push阻塞推送
		static::parsePushBlock($messageId);
	}

	/**
	 * 队列回滚
	 *
	 * @param string $queueId
	 * @param string $messageId
	 * @return void
	 */
	public static function rollbackPop($queueId, $messageId)
	{
		PoolManager::use('redis', function($source, $redis) use($queueId, $messageId){
			$workingMessageSetKey = RedisKey::getWorkingMessageSet($queueId);

			// 移出工作集合
			$redis->zrem($workingMessageSetKey, $messageId);

			// 加入队列
			$redis->lpush(RedisKey::getMessageQueue($queueId), $messageId);
		});
	}

	/**
	 * 消息处理完成
	 *
	 * @param \SixMQ\Struct\Queue\Client\Complete $data
	 * @return void
	 */
	public static function complete($data)
	{
		$result = PoolManager::use('redis', function($resource, $redis) use($data){
			// 取出消息数据
			$message = $redis->get($data->messageId);

			if(false === $message)
			{
				return null;
			}
			// 开启事务
			$redis->multi();

			// 移出集合队列
			$redis->zrem(RedisKey::getWorkingMessageSet($data->queueId), $data->messageId);

			// 移除队列（先触发了失败重新入队，尝试出列）
			$redis->lrem(RedisKey::getMessageQueue($data->queueId), $data->messageId, 1);

			$message->consum = true;
			$message->success = $data->success;
			$message->resultData = $data->data;

			// 消息消费失败
			if(!$data->success)
			{
				// 加入队列
				$redis->rpush(RedisKey::getMessageQueue($data->queueId), $data->messageId);
				$message->inTime = time();
			}

			// 设置消息数据
			$redis->set($data->messageId, $message);

			// 运行事务
			return $redis->exec();
		});
		$return = new Reply(null !== $result);
		// 处理push阻塞推送
		static::parsePushBlock($data->messageId);
		return $return;
	}

	/**
	 * 获取消息数据
	 *
	 * @param string $messageId
	 * @return \SixMQ\Struct\Queue\Message|boolean
	 */
	public static function getMessage($messageId)
	{
		$result = PoolManager::use('redis', function($resource, $redis) use($messageId){
			return $redis->get($messageId);
		});
		return $result;
	}

	/**
	 * 处理push阻塞推送
	 *
	 * @param string $messageId
	 * @return void
	 */
	private static function parsePushBlock($messageId)
	{
		if(RequestContext::exsits())
		{
			$server = RequestContext::get('server');
		}
		else
		{
			$server = ServerManage::getServer('MQService');
		}
		// 处理push阻塞推送
		go(function() use($messageId, $server){
			RequestContext::create();
			RequestContext::set('server', $server);
			QueuePushBlockParser::complete($messageId);
			RequestContext::destroy();
		});
	}
}