<?php
namespace SixMQ\Api\Service;

use Imi\Util\Imi;
use Imi\Util\File;
use Imi\RequestContext;
use Imi\Bean\Annotation\Bean;
use Imi\Server\Session\Session;

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
        $file = File::path(Imi::getNamespacePath('SixMQ\config'), 'auth.json');
        $data = json_decode(file_get_contents($file), true);
        if(!isset($data['accounts'][$username]) || $data['accounts'][$username]['password'] !== $password)
        {
            throw new \RuntimeException('登录失败');
        }
        Session::set('AUTH', [
            'username'  =>  $username,
            'ip'        =>  RequestContext::get('request')->getServerParam('remote_addr'),
        ]);
    }

}