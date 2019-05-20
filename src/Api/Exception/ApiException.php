<?php
namespace SixMQ\Api\Exception;

use SixMQ\Api\Enums\ApiStatus;


class ApiException extends \RuntimeException
{
    public function __construct($code = ApiStatus::ERROR, $message = null, $previous = null)
    {
        if(null === $message)
        {
            $message = ApiStatus::getText($code);
        }
        parent::__construct($message, $code, $previous);
    }

    public static function fromCode($code, $message = null, $previous = null)
    {
        return new static($code, $message, $previous);
    }

    public static function fromMessage($message, $code = ApiStatus::ERROR, $previous = null)
    {
        return new static($code, $message, $previous);
    }

}