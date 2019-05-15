<?php
namespace SixMQ\Api\Service;

use Imi\RequestContext;
use Imi\Bean\Annotation\Bean;
use Imi\Server\Session\Session;

/**
 * @Bean("ApiMemberSessionService")
 */
class ApiMemberSessionService
{
    /**
     * 是否登录
     *
     * @var boolean
     */
    protected $isLogin = false;

    /**
     * 用户名
     *
     * @var string
     */
    protected $username;

    public function __init()
    {
        $this->init();
    }

    /**
     * 初始化
     *
     * @return void
     */
    public function init()
    {
        $auth = Session::get('AUTH');
        if(null === $auth)
        {
            return;
        }
        
        if($auth['ip'] != RequestContext::get('request')->getServerParam('remote_addr'))
        {
            return;
        }

        $this->username = $auth['username'];
        $this->isLogin = true;
    }

    /**
     * 是否登录
     *
     * @return boolean
     */
    public function isLogin()
    {
        return $this->isLogin;
    }

}