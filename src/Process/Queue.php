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
        // 消息超时
        $this->goTask(function(){
            foreach(QueueCollection::getList() as $queueId)
            {
                $this->checkMessageExpire($queueId);
            }
        });
        // 任务超时
        $this->goTask(function(){
            foreach(QueueCollection::getList() as $queueId)
            {
                $this->checkTaskExpire($queueId);
            }
        });
        // 消息延迟
        $this->goTask(function(){
            $this->parseDelayMessage();
        });
    }

    /**
     * 启动一个协程执行任务
     *
     * @param callable $callable
     * @param int $minTimespan
     * @return void
     */
    private function goTask($callable, $minTimespan = 1)
    {
        go(function() use($callable, $minTimespan){
            while(true)
            {
                $beginTime = microtime(true);
                
                $callable();

                $subTime = microtime(true) - $beginTime;
                if($subTime < $minTimespan)
                {
                    Coroutine::sleep($minTimespan - $subTime);
                }
                else
                {
                    Coroutine::sleep(0.001);
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
        PoolManager::use('redis', function($resource, $redis) use($queueId){
            $expireMessageSetKey = RedisKey::getQueueExpireSet($queueId);
            do{
                $list = $redis->zrevrangebyscore($expireMessageSetKey, microtime(true), 0, [
                    'limit'     =>  [0, 1000],
                ]);
                foreach($list as $messageId)
                {
                    QueueService::expireMessage($queueId, $messageId);
                }
            }while([] !== $list);
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
        PoolManager::use('redis', function($resource, $redis) use($queueId){
            $workingMessageSetKey = RedisKey::getWorkingMessageSet($queueId);
            do{
                $list = $redis->zrevrangebyscore($workingMessageSetKey, microtime(true), 0, [
                    'limit'     =>  [0, 1000],
                ]);
                foreach($list as $messageId)
                {
                    QueueService::expireTask($queueId, $messageId);
                }
            }while([] !== $list);
        });
    }

    /**
     * 消息延迟处理
     *
     * @return void
     */
    private function parseDelayMessage()
    {
        PoolManager::use('redis', function($resource, $redis){
            $key = RedisKey::getDelaySet();
            do{
                $list = $redis->zrevrangebyscore($key, microtime(true), 0, [
                    'limit'     =>  [0, 1000],
                ]);
                foreach($list as $messageId)
                {
                    QueueService::delayToQueue($messageId);
                }
            }while([] !== $list);
        });
    }
}