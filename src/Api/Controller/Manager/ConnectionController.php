<?php
namespace SixMQ\Api\Controller\Manager;

use Imi\Worker;
use Imi\ConnectContext;
use Imi\Aop\Annotation\Inject;
use Imi\Controller\HttpController;
use Imi\Server\Route\Annotation\Route;
use Imi\Server\Route\Annotation\Action;
use Imi\Server\Route\Annotation\Controller;
use Imi\Server\Route\Annotation\Middleware;
use SixMQ\Api\Exception\ApiException;

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

    /**
     * 断开连接
     * 
     * @Action
     * 
     * @Route(method="POST")
     *
     * @param int $fd
     * @return void
     */
    public function close($fd)
    {
        if(!$this->request->getServerInstance()->getSwooleServer()->close($fd))
        {
            throw ApiException::fromMessage('断开失败');
        }
    }

}