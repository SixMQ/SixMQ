<?php
namespace SixMQ\Api\ErrorHandler;

use Imi\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Imi\Server\Http\Error\IHttpNotFoundHandler;

class HttpNotFoundHandler implements IHttpNotFoundHandler
{
    public function handle(RequestHandlerInterface $requesthandler, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if('/favicon.ico' === $request->getUri()->getPath())
        {
            return RequestContext::get('response')->withStatus(404);
        }
        else
        {
            throw new \RuntimeException('404 Not Found');
        }
    }
}