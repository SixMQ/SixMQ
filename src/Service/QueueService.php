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

abstract class QueueService
{
	/**
	 * 消息入队列
	 *
	 * @param \SixMQ\Struct\Queue\Client\Push $data
	 * @return \SixMQ\Struct\Queue\Server\Reply
	 */
	public static function push($data)
	{
		$result = PoolManager::use('redis', function($resource, $redis) use($data){
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
		$return = new Reply($success);
		go(function() use($success, $data){
			// 队列记录
			if($success && !QueueCollection::has($data->queueId))
			{
				QueueCollection::append($data->queueId);
			}
			// 阻塞请求支持
			if(CoroutineChannelManager::stats('BlockQueue')['consumer_num'] > 0)
			{
				CoroutineChannelManager::push('BlockQueue', true);
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

		if(!$result && $data->block)
		{
			// 没有可弹出的消息，并且是阻塞请求
			// TODO:超时时间
			do{
				if(!CoroutineChannelManager::pop('BlockQueue', Config::get('@app.common.queue_block_time')))
				{
					break;
				}
			}while(!($result = static::tryPop($data, $messageId, $message)) && ConnectContext::exsits());
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

			// 设置消息数据
			$redis->set($messageId, $message);
		});
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
		return $return;
	}
}