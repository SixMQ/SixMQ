<?php
namespace SixMQ\MQService\Controller;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use SixMQ\Util\GenerateID;
use SixMQ\Struct\Queue\Message;
use SixMQ\Struct\BaseServerStruct;
use SixMQ\Struct\Queue\Server\Pop;
use SixMQ\Util\QueueCollection;
use Imi\Server\Route\Annotation\Tcp\TcpRoute;
use Imi\Server\Route\Annotation\Tcp\TcpAction;
use Imi\Server\Route\Annotation\Tcp\TcpController;
use SixMQ\Struct\Queue\Server\Reply;

/**
 * @TcpController
 */
class Queue extends Base
{
	/**
	 * 消息入队列
	 * @TcpAction
	 * @TcpRoute({"action"="queue.push"})
	 *
	 * @param \SixMQ\Struct\Queue\Client\Push $data
	 * @return void
	 */
	public function push($data)
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
		$this->reply($return);
		// 队列记录
		if($success && !QueueCollection::has($data->queueId))
		{
			QueueCollection::append($data->queueId);
		}
	}

	/**
	 * 消息出队列
	 * @TcpAction
	 * @TcpRoute({"action"="queue.pop"})
	 *
	 * @param \SixMQ\Struct\Queue\Client\Pop $data
	 * @return void
	 */
	public function pop($data)
	{
		$result = PoolManager::use('redis', function($resource, $redis) use($data, &$messageId, &$message){
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
		$return = new Pop($result);
		if($result)
		{
			$return->queueId = $data->queueId;
			$return->messageId = $messageId;
			$return->data = $message;
		}
		$this->reply($return);
	}

	/**
	 * 消息处理完成
	 * @TcpAction
	 * @TcpRoute({"action"="queue.complete"})
	 *
	 * @param \SixMQ\Struct\Queue\Client\Complete $data
	 * @return void
	 */
	public function complete($data)
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
		$this->reply($return);
	}
}