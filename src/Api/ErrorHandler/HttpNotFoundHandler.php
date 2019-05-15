<?php
namespace SixMQ\Api\ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Imi\Server\Http\Error\IHttpNotFoundHandler;
use Imi\RequestContext;

class HttpNotFoundHandler implements IHttpNotFoundHandler
{
    public function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
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