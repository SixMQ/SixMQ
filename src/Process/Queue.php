<?php
namespace SixMQ\Process;

use Imi\Process\BaseProcess;
use Imi\Process\Annotation\Process;
use Imi\Pool\PoolManager;
use SixMQ\Util\RedisKey;
use SixMQ\Util\QueueCollection;
use SixMQ\Service\QueueService;
use Swoole\Coroutine;

/**
 * @Process(name="SixMQQueueMonitor", unique=true)
 */
class Queue extends BaseProcess
{
    public function run(\Swoole\Process $process)
    {
        echo 'Process [SixMQQueueMonitor] start', PHP_EOL;
        go(function(){
            while(true)
            {
                $beginTime = microtime(true);
                foreach(QueueCollection::getList() as $queueId)
                {
                    $this->checkMessageExpire($queueId);
                    $this->checkTaskExpire($queueId);
                }

                $subTime = microtime(true) - $beginTime;
                if($subTime < 1)
                {
                    Coroutine::sleep(1 - $subTime);
                }
            }
        });
    }

    /**
     * 检查消息超时
     *
     * @param string $queueId
     * @return void
     */
    private function checkMessageExpire($queueId)
    {
        PoolManager::use('redis', function($source, $redis) use($queueId){
            $expireMessageSetKey = RedisKey::getQueueExpireSet($queueId);
            $list = $redis->zrevrangebyscore($expireMessageSetKey, microtime(true), 0, []);
            foreach($list as $messageId)
            {
                QueueService::expireMessage($queueId, $messageId);
            }
        });
    }

    /**
     * 检查任务超时
     *
     * @param string $queueId
     * @return void
     */
    private function checkTaskExpire($queueId)
    {
        PoolManager::use('redis', function($source, $redis) use($queueId){
            $workingMessageSetKey = RedisKey::getWorkingMessageSet($queueId);
            $list = $redis->zrevrangebyscore($workingMessageSetKey, microtime(true), 0, []);
            foreach($list as $messageId)
            {
                QueueService::expireTask($queueId, $messageId);
            }
        });
    }
}