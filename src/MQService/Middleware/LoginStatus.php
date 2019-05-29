<?php
namespace SixMQ\MQService\Middleware;

use Imi\Config;
use Imi\ConnectContext;
use Imi\RequestContext;
use SixMQ\Api\Enums\ApiStatus;
use SixMQ\Api\Exception\ApiException;
use Imi\Server\TcpServer\IReceiveHandler;
use Imi\Server\TcpServer\Message\IReceiveData;
use Imi\Server\TcpServer\Middleware\IMiddleware;
use SixMQ\Struct\Queue\Server\Reply;

/**
 * 登录状态验证中间件
 */
class LoginStatus implements IMiddleware
{
    public function process(IReceiveData $data, IReceiveHandler $handler)
    {
        if(Config::get('auth.serviceValidateAccount') && !ConnectContext::get('username'))
        {
            $reply = new Reply(false);
            $reply->error = 'Auth Failed';
            return $reply;
        }
        return $handler->handle($data);
    }
}