<?php
namespace SixMQ\Api\Service;

use SixMQ\Logic\QueueLogic;
use Imi\Bean\Annotation\Bean;
use SixMQ\Logic\MessageCountLogic;
use SixMQ\Logic\MessageWorkingLogic;
use SixMQ\Api\Enums\MessageStatus;
use SixMQ\Logic\MessageLogic;
use SixMQ\Util\QueueError;
use Imi\Util\ObjectArrayHelper;

/**
 * @Bean("ApiMessageService")
 */
class ApiMessageService
{
    /**
     * 消息列表
     * 
     * @param string $queueId
     * @param integer $page
     * @param integer $count
     * @param integer $status
     * @return void
     */
    public function selectList($queueId, $page, $count, &$pages = 0, $status = 0)
    {
        switch((int)$status)
        {
            case MessageStatus::FREE:
                $messageIds = QueueLogic::selectMessageIds($queueId, $page, $count, $pages);
                break;
            case MessageStatus::WORKING:
                $messageIds = MessageWorkingLogic::selectMessageIds($queueId, $page, $count, $pages);
                break;
            case MessageStatus::FAIL:
                $messageIds = MessageCountLogic::selectFailMessageIds($queueId, $page, $count, $pages);
                break;
            default:
                $messageIds = QueueLogic::selectAllMessageIds($queueId, $page, $count, $pages);
                break;
        }
        $messages = MessageLogic::select($messageIds);
        foreach($messages as $i => $message)
        {
            $messages[$i] = $this->parseGet($message);
        }
        return $messages;
    }

    /**
     * 获取消息
     *
     * @param string $messageId
     * @return \SixMQ\Struct\Queue\Message
     */
    public function get($messageId)
    {
        $message = QueueService::getMessage($messageId);
        return $this->parseGet($message);
    }

    /**
     * 处理消息
     *
     * @param \SixMQ\Struct\Queue\Message $message
     * @return \SixMQ\Struct\Queue\Message
     */
    protected function parseGet($message)
    {
        ObjectArrayHelper::set($message, 'errorCount', QueueError::get($message->messageId));
        return $message;
    }
}