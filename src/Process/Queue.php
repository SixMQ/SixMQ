<?php
namespace SixMQ\Process;

use Imi\Process\BaseProcess;
use Imi\Process\Annotation\Process;
use Imi\Pool\PoolManager;
use SixMQ\Util\RedisKey;
use SixMQ\Util\QueueCollection;
use SixMQ\Service\QueueService;

/**
 * @Process(name="SixMQQueueMonitor", unique=true)
 */
class Queue extends BaseProcess
{
	public function run(\Swoole\Process $process)
	{
		echo 'Process [SixMQQueueMonitor] start', PHP_EOL;
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
				QueueService::expireMessage($queueId, $messageId);
			}
		});
	}
}