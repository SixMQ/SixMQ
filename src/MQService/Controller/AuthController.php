<?php
namespace SixMQ\MQService\Controller;

use Imi\Aop\Annotation\Inject;
use Imi\Server\Route\Annotation\Tcp\TcpRoute;
use Imi\Server\Route\Annotation\Tcp\TcpAction;
use Imi\Server\Route\Annotation\Tcp\TcpController;
use SixMQ\Struct\Queue\Server\Auth\Login;
use Imi\ConnectContext;

/**
 * @TcpController
 */
class AuthController extends Base
{
    /**
     * @Inject("AuthService")
     *
     * @var \SixMQ\MQService\Service\AuthService
     */
    protected $authService;

    /**
     * 登录
     * 
     * @TcpAction
     * @TcpRoute({"action"="auth.login"})
     * 
     * @param string $username
     * @param string $password
     * 
     * @param \SixMQ\Struct\Queue\Client\Auth\Login $data
     * @return \SixMQ\Struct\Queue\Server\Auth\Login
     */
    public function login($data)
    {
        $result = $this->authService->login($data->username, $data->password);
        if($result)
        {
            ConnectContext::set('username', $data->username);
        }
        $reply = new Login($result);
        if(!$result)
        {
            $reply->error = 'Login failed';
        }
        $this->reply($reply);
        if(!$result)
        {
            $this->server->getSwooleServer()->close($this->data->getFd());
        }
    }

}