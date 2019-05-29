<?php
namespace SixMQ\Process;

use Swoole\Coroutine;
use Imi\Process\BaseProcess;
use SixMQ\Logic\MessageExpire;
use SixMQ\Logic\MessageCountLogic;
use Imi\Process\Annotation\Process;
use SixMQ\Logic\QueueLogic;

/**
 * @Process(name="SixMQ-MessageTTL", unique=true)
 */
class MessageTTL extends BaseProcess
{
    public function run(\Swoole\Process $process)
    {
        echo 'Process [SixMQ-MessageTTL] start', PHP_EOL;
        $this->goTask(function(){
            $this->parseMessageTTL();
        }, 1);
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
        imigo(function() use($callable, $minTimespan){
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

    protected function parseMessageTTL()
    {
        while($data = MessageExpire::getExpireMessages(1000))
        {
            foreach($data as $item)
            {
                MessageCountLogic::removeSuccessMessage($item['messageId'], $item['queueId']);
                MessageCountLogic::removeFailedMessage($item['messageId'], $item['queueId']);
                MessageCountLogic::removeTimeoutMessage($item['messageId'], $item['queueId']);
                QueueLogic::removeFromAll($item['queueId'], $item['messageId']);
            }
            MessageExpire::removeMessages($data);
        }
    }
}