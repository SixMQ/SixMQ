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
 * @Controller("/message/")
 * @Middleware(\SixMQ\Api\Middlewares\LoginStatus::class)
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

}