<?php
namespace SixMQ\Logic;

use SixMQ\Struct\Util\MessageStatus;

class MessageStatusLogic
{
    /**
     * 移除状态记录
     *
     * @param string $messageId
     * @param string $queueId
     * @param int $status
     * @return void
     */
    public static function removeStatusRecord($messageId, $queueId, $status)
    {
        switch($status)
        {
            case MessageStatus::FREE:
                break;
            case MessageStatus::WORKING:
                break;
            case MessageStatus::SUCCESS:
                MessageCountLogic::removeSuccessMessage($messageId, $queueId);
                break;
            case MessageStatus::FAIL:
                MessageCountLogic::removeFailedMessage($messageId, $queueId);
                break;
            case MessageStatus::TIMEOUT:
                MessageCountLogic::removeTimeoutMessage($messageId, $queueId);
                break;
        }
    }

    /**
     * 增加状态记录
     *
     * @param string $messageId
     * @param string $queueId
     * @param int $status
     * @return void
     */
    public static function addStatusRecord($messageId, $queueId, $status)
    {
        switch($status)
        {
            case MessageStatus::FREE:
                break;
            case MessageStatus::WORKING:
                break;
            case MessageStatus::SUCCESS:
                MessageCountLogic::addSuccessMessage($messageId, $queueId);
                break;
            case MessageStatus::FAIL:
                MessageCountLogic::addFailedMessage($messageId, $queueId);
                break;
            case MessageStatus::TIMEOUT:
                MessageCountLogic::addTimeoutMessage($messageId, $queueId);
                break;
        }
    }

}