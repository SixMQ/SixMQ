<?php
namespace SixMQ\Api\Controller\Manager;

use SixMQ\Logic\QueueLogic;
use Imi\Aop\Annotation\Inject;
use Imi\Controller\HttpController;
use SixMQ\Logic\MessageCountLogic;
use SixMQ\Logic\MessageWorkingLogic;
use Imi\Server\Route\Annotation\Route;
use Imi\Server\Route\Annotation\Action;
use Imi\Server\Route\Annotation\Controller;
use Imi\Server\Route\Annotation\Middleware;

/**
 * @Controller("/queue/")
 * @Middleware(\SixMQ\Api\Middleware\LoginStatus::class)
 */
class QueueController extends HttpController
{
    /**
     * @Inject("ApiQueueService")
     *
     * @var \SixMQ\Api\Service\ApiQueueService
     */
    protected $apiQueueService;

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
            $list[] = $this->apiQueueService->getQueueInfo($queueId);
        }
        return [
            'list'   =>  $list,
        ];
    }

    /**
     * 获取队列详情
     * 
     * @Action
     *
     * @param string $queueId
     * @return void
     */
    public function get($queueId)
    {
        return [
            'data'  =>  $this->apiQueueService->getQueueInfo($queueId),
        ];
    }
}