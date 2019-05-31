<?php
namespace SixMQ\Process;

use Swoole\Coroutine;
use SixMQ\Logic\MessageExpire;
use SixMQ\Logic\MessageCountLogic;
use Imi\Process\Annotation\Process;
use SixMQ\Logic\QueueLogic;
use SixMQ\Logic\MessageGroupLogic;

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
                // 分组删除
                MessageGroupLogic::remove($item['queueId'], $item['groupId'], $item['messageId']);
            }
            MessageExpire::removeMessages($data);
        }
    }
}