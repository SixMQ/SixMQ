<?php
namespace SixMQ\Api\ErrorHandler;

use Imi\App;
use Imi\RequestContext;
use Imi\Util\Http\Consts\MediaType;
use Imi\Util\Http\Consts\RequestHeader;
use Imi\Server\Http\Error\IErrorHandler;

class HttpErrorHandler implements IErrorHandler
{
    public function handle(\Throwable $throwable): bool
    {
        if($throwable instanceof ApiException)
        {
            $cancelThrow = true;
        }
        else
        {
            $cancelThrow = false;
            $code = 500;
        }
        $data = [
            'success'    =>    false,
            'status'    =>  $code ?? $throwable->getCode(),
            'message'    =>    $throwable->getMessage(),
        ];
        if(App::isDebug())
        {
            $data['exception'] = [
                'message'    =>    $throwable->getMessage(),
                'code'        =>    $throwable->getCode(),
                'file'        =>    $throwable->getFile(),
                'line'        =>    $throwable->getLine(),
                'trace'        =>    explode(PHP_EOL, $throwable->getTraceAsString()),
            ];
        }
        RequestContext::get('response')
        ->withAddedHeader(RequestHeader::CONTENT_TYPE, MediaType::APPLICATION_JSON)
        ->write(json_encode($data))
        ->send();
        return $cancelThrow;
    }
}