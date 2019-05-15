<?php
namespace SixMQ\Api\Service;

use SixMQ\Logic\QueueLogic;
use Imi\Bean\Annotation\Bean;
use SixMQ\Logic\MessageCountLogic;
use SixMQ\Logic\MessageWorkingLogic;
use SixMQ\Api\Enums\MessageStatus;
use SixMQ\Logic\MessageLogic;

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
     * @param integer $type
     * @return void
     */
    public function selectList($queueId, $page, $count, &$pages = 0, $type = 0)
    {
        switch((int)$type)
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
        return MessageLogic::select($messageIds);
    }

}