<?php
namespace SixMQ\Api\Controller\Manager;

use Imi\Controller\HttpController;
use Imi\Server\Route\Annotation\Route;
use Imi\Server\Route\Annotation\Action;
use Imi\Server\Route\Annotation\Controller;
use SixMQ\Logic\QueueLogic;
use SixMQ\Logic\MessageCountLogic;
use SixMQ\Logic\MessageWorkingLogic;

/**
 * @Controller("/queue/")
 */
class QueueController extends HttpController
{
    /**
     * 队列列表
     * 
     * @Action
     * 
     * @return void
     */
    public function list()
    {
        $queueList = QueueLogic::getList();
        $list = [];
        foreach($queueList as $queueId)
        {
            $list = [
                'name'          =>  $queueId,
                // 消息总数
                'messageCount'  =>  MessageCountLogic::getQueueMessageCount($queueId),
                // 正在工作数量
                'workingCount'  =>  MessageWorkingLogic::count($queueId),
                // 等待处理数量
                'waitingCount'  =>  QueueLogic::count($queueId),
                // 失败数量
                'failCount'     =>  MessageCountLogic::getFailedMessageCount($queueId),
            ];
        }
        return [
            'list'   =>  $list,
        ];
    }

}