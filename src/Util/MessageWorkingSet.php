<?php
namespace SixMQ\Util;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;

abstract class MessageWorkingSet
{
    /**
     * 新增工作中的消息
     *
     * @param string $queueId
     * @param string $messageId
     * @param float $expireTime
     * @return void
     */
    public static function add($queueId, $messageId, $expireTime)
    {
        return PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId, $expireTime) {
            $redis->zAdd(RedisKey::getWorkingMessageSet($queueId), $expireTime, $messageId);
        });
    }

    /**
     * 移出工作队列
     *
     * @param string $queueId
     * @param string $messageId
     * @return void
     */
    public static function remove($queueId, $messageId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId) {
            $redis->zRem(RedisKey::getWorkingMessageSet($queueId), $messageId);
        });
    }

    /**
     * 是否正在工作队列中
     *
     * @param string $queueId
     * @param string $messageId
     * @return boolean
     */
    public static function exists($queueId, $messageId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId) {
            $redis->zRank(RedisKey::getWorkingMessageSet($queueId), $messageId);
        });
    }
}