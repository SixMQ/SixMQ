<?php
namespace SixMQ\Api\Controller\Manager;

use Imi\Aop\Annotation\Inject;
use Imi\Controller\HttpController;
use Imi\Server\Route\Annotation\Action;
use Imi\Server\Route\Annotation\Controller;
use Imi\Server\Route\Annotation\Middleware;
use Imi\Worker;
use Imi\ConnectContext;

/**
 * @Controller("/connection/")
 * @Middleware(\SixMQ\Api\Middleware\LoginStatus::class)
 */
class ConnectionController extends HttpController
{
    /**
     * @Inject("ConnectionService")
     *
     * @var \SixMQ\Service\ConnectionService
     */
    protected $connectionService;

    /**
     * 查询连接
     * 
     * @Action
     *
     * @param integer $page
     * @param integer $count
     * @return void
     */
    public function select($page = 1, $count = 15)
    {
        $list = $this->connectionService->selectList($page, $count, $pages);
        return [
            'list'  =>  $list,
            'page'  =>  $page,
            'count' =>  $count,
            'pages' =>  $pages,
        ];
    }

}