<?php
namespace SixMQ\Api\Middleware;

use Imi\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 跨域
 */
class CrossDomain implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = RequestContext::get('response');
        if($response instanceof \Imi\Server\Http\Message\Response)
        {
            $origin = $request->getHeaderLine('origin');
            if($origin)
            {
                $response = $response->withAddedHeader('Access-Control-Allow-Origin', $origin);
                $response = $response->withAddedHeader('Access-Control-Allow-Credentials', 'true');
                RequestContext::set('response', $response);
            }
        }
        return $handler->handle($request);
    }
}