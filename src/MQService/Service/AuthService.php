<?php
namespace SixMQ\MQService\Service;

use Imi\Config;
use Imi\Bean\Annotation\Bean;

/**
 * @Bean("AuthService")
 */
class AuthService
{
    /**
     * 登录
     *
     * @param string $username
     * @param string $password
     * @return boolean
     */
    public function login($username, $password)
    {
        if(Config::get('auth.accounts.' . $username . '.password') !== $password)
        {
            return false;
        }
        return true;
    }
}