<?php
namespace SixMQ\WorkerListener;

use Imi\Bean\Annotation\Listener;
use Imi\Event\IEventListener;
use Imi\Event\EventParam;
use SixMQ\Struct\Queue\Message;
use SixMQ\Logic\MessageStatusLogic;

/**
 * @Listener(eventName="SIXMQ.MESSAGE.CHANGE_STATUS")
 */
class OnMessageChangeStatus implements IEventListener
{
    /**
     * 事件处理方法
     * @param \Imi\Event\EventParam $e
     * @return void
     */
    public function handle(EventParam $e)
    {
        $message = $e->getData()['message'];
        if(!$message instanceof Message)
        {
            return;
        }
        MessageStatusLogic::removeStatusRecord($message->messageId, $message->queueId, $message->getOriginStatus());
        MessageStatusLogic::addStatusRecord($message->messageId, $message->queueId, $message->status);
    }

}