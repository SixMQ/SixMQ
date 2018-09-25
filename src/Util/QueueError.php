<?php
namespace SixMQ\Util;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;

abstract class QueueError
{
    /**
     * 累加错误计数
     *
     * @param string $messageId
     * @return int
     */
    public static function inc($messageId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($messageId) {
            return $redis->hIncrBy(RedisKey::getMessageErrorCount(), $messageId, 1);
        });
    }

    /**
     * 获取错误计数值
     *
     * @param string $messageId
     * @return int
     */
    public static function get($messageId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($messageId) {
            return $redis->hGet(RedisKey::getMessageErrorCount(), $messageId);
        });
    }
    
}