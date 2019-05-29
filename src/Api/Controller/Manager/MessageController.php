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
use SixMQ\Service\QueueService;
use SixMQ\Logic\MessageLogic;

/**
 * @Controller("/message/")
 * @Middleware(\SixMQ\Api\Middleware\LoginStatus::class)
 */
class MessageController extends HttpController
{
    /**
     * @Inject("ApiMessageService")
     *
     * @var \SixMQ\Api\Service\ApiMessageService
     */
    protected $apiMessageService;

    /**
     * 消息列表
     * 
     * @Action
     *
     * @param string $queueId
     * @param integer $page
     * @param integer $count
     * @param integer $status
     * @return void
     */
    public function list($queueId, $page = 1, $count = 15, $status = 0)
    {
        $list = $this->apiMessageService->selectList($queueId, $page, $count, $pages, $status);
        return [
            'list'  =>  $list,
            'page'  =>  $page,
            'count' =>  $count,
            'pages' =>  $pages,
        ];
    }

    /**
     * 分组消息列表
     * 
     * @Action
     *
     * @param string $queueId
     * @param string $groupId
     * @param integer $page
     * @param integer $count
     * @return void
     */
    public function listByGroup($queueId, $groupId, $page = 1, $count = 15)
    {
        $list = $this->apiMessageService->selectListByGroup($queueId, $groupId, $page, $count, $pages);
        return [
            'list'  =>  $list,
            'page'  =>  $page,
            'count' =>  $count,
            'pages' =>  $pages,
        ];
    }

    /**
     * 获取消息详情
     * 
     * @Action
     *
     * @param string $messageId
     * @return void
     */
    public function get($messageId)
    {
        return [
            'data'  =>  $this->apiMessageService->get($messageId),
        ];
    }

    /**
     * 重新推送消息进队列
     *
     * @Action
     * 
     * @Route(method="POST")
     * 
     * @param string $messageId
     * @return void
     */
    public function repush($messageId)
    {
        MessageLogic::repush($messageId);
    }

}