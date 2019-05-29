<?php
namespace SixMQ\Api\Service;

use SixMQ\Logic\QueueLogic;
use Imi\Bean\Annotation\Bean;
use SixMQ\Logic\MessageCountLogic;
use SixMQ\Logic\MessageWorkingLogic;

/**
 * @Bean("ApiQueueService")
 */
class ApiQueueService
{
    /**
     * 获取队列信息
     *
     * @param string $queueId
     * @return array
     */
    public function getQueueInfo($queueId)
    {
        return [
            'name'          =>  $queueId,
            // 消息总数
            'messageCount'  =>  MessageCountLogic::getQueueMessageCount($queueId),
            // 正在工作数量
            'workingCount'  =>  MessageWorkingLogic::count($queueId),
            // 等待处理数量
            'waitingCount'  =>  QueueLogic::count($queueId),
            // 成功数量
            'successCount'  =>  MessageCountLogic::getSuccessMessageCount($queueId),
            // 失败数量
            'failCount'     =>  MessageCountLogic::getFailedMessageCount($queueId),
            // 超时数量
            'timeoutCount'  =>  MessageCountLogic::getTimeoutMessageCount($queueId),
        ];
    }

}