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

/**
 * @Controller("/auth/")
 */
class AuthController extends HttpController
{
    /**
     * @Inject("ApiAuthService")
     *
     * @var \SixMQ\Api\Service\ApiAuthService
     */
    protected $ApiAuthService;

    /**
     * 登录
     * 
     * @Action
     * 
     * @Route(method="POST")
     * 
     * @param string $username
     * @param string $password
     * 
     * @return void
     */
    public function login($username, $password)
    {
        $this->ApiAuthService->login($username, $password);
    }

}