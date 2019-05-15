<?php
namespace SixMQ\Api\Middlewares;

use Imi\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 登录状态验证中间件
 */
class LoginStatus implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $memberSession = RequestContext::getBean('ApiMemberSessionService');
        if(!$memberSession->isLogin())
        {
            throw new \RuntimeException('未登录');
        }
        return $handler->handle($request);
    }
}