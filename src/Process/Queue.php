<?php
namespace SixMQ\Process;

use Imi\Process\BaseProcess;
use Imi\Process\Annotation\Process;
use Imi\Pool\PoolManager;
use SixMQ\Util\RedisKey;
use SixMQ\Util\QueueCollection;

/**
 * @Process(name="SixMQQueueMonitor", unique=true)
 */
class Queue extends BaseProcess
{
	public function run(\Swoole\Process $process)
	{
		while(true)
		{
			foreach(QueueCollection::getList() as $queueId)
			{
				$this->checkExpire($queueId);
			}
			sleep(1);
		}
	}

	private function checkExpire($queueId)
	{
		PoolManager::use('redis', function($source, $redis) use($queueId){
			$workingMessageSetKey = RedisKey::getWorkingMessageSet($queueId);
			$list = $redis->zrevrangebyscore($workingMessageSetKey, microtime(true), 0, []);
			foreach($list as $messageId)
			{
				// 消息执行超时
				$message = $redis->get($messageId);

				// 移出集合队列
				$redis->zrem($workingMessageSetKey, $messageId);

				$message->success = false;
				$message->resultData = 'timeout';

				// 加入队列
				$redis->rpush(RedisKey::getMessageQueue($queueId), $messageId);
				$message->inTime = time();

				// 设置消息数据
				var_dump($message);
				$redis->set($messageId, $message);
			}
		});
	}
}