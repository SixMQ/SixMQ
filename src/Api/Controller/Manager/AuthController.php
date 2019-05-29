<?php
namespace SixMQ\Api\Controller\Manager;

use SixMQ\Logic\QueueLogic;
use Imi\Aop\Annotation\Inject;
use Imi\Controller\HttpController;
use SixMQ\Logic\MessageCountLogic;
use SixMQ\Logic\MessageWorkingLogic;
use Imi\Aop\Annotation\RequestInject;
use Imi\Server\Route\Annotation\Route;
use Imi\Server\Route\Annotation\Action;
use Imi\Server\Route\Annotation\Controller;
use Imi\Server\Route\Annotation\Middleware;

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
    protected $authService;

    /**
     * @RequestInject("ApiMemberSessionService")
     *
     * @var \SixMQ\Api\Service\ApiMemberSessionService
     */
    protected $memberSessionService;

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
        $this->authService->login($username, $password);
    }

    /**
     * 登录状态
     * 
     * @Action
     *
     * @Middleware(\SixMQ\Api\Middleware\LoginStatus::class)
     * 
     * @return void
     */
    public function status()
    {
        return [
            'data'  =>  [
                'username'  =>  $this->memberSessionService->getUsername(),
            ],
        ];
    }
}