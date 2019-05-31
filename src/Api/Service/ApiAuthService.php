<?php
namespace SixMQ\Api\Service;

use Imi\Util\Imi;
use Imi\Util\File;
use Imi\RequestContext;
use Imi\Bean\Annotation\Bean;
use Imi\Server\Session\Session;
use Imi\Config;

/**
 * @Bean("ApiAuthService")
 */
class ApiAuthService
{
    /**
     * 登录
     *
     * @param string $username
     * @param string $password
     * @return void
     */
    public function login($username, $password)
    {
        if(Config::get('auth.accounts.' . $username . '.password') !== $password)
        {
            throw new \RuntimeException('登录失败');
        }
        Session::set('AUTH', [
            'username'  =>  $username,
            'ip'        =>  RequestContext::get('request')->getServerParam('remote_addr'),
        ]);
    }

}