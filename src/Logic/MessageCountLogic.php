<?php
namespace SixMQ\Logic;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use Imi\Redis\RedisHandler;

/**
 * 消息统计
 */
abstract class MessageCountLogic
{
    /**
     * 队列消息数累加
     *
     * @param string $queueId
     * @return boolean
     */
    public static function incQueueMessage($queueId)
    {
        return PoolManager::use('redis', function($resource, RedisHandler $redis) use($queueId) {
            $key = RedisKey::getQueueMessageCount();
            return $redis->hIncrBy($key, $queueId, 1);
        }) > 0;
    }

    /**
     * 获取队列消息总数
     *
     * @param string $queueId
     * @return int
     */
    public static function getQueueMessageCount($queueId)
    {
        return (int)PoolManager::use('redis', function($resource, RedisHandler $redis) use($queueId) {
            $key = RedisKey::getQueueMessageCount();
            return $redis->hget($key, $queueId);
        });
    }

    /**
     * 增加统计失败消息
     *
     * @param string $messageId
     * @param string $queueId
     * @return void
     */
    public static function addFailedMessage($messageId, $queueId)
    {
        return PoolManager::use('redis', function($resource, RedisHandler $redis) use($messageId, $queueId) {
            $key = RedisKey::getFailedList($queueId);
            return $redis->lpush($key, $messageId);
        });
    }

    /**
     * 移除统计失败消息
     *
     * @param string $messageId
     * @param string $queueId
     * @return void
     */
    public static function removeFailedMessage($messageId, $queueId)
    {
        return PoolManager::use('redis', function($resource, RedisHandler $redis) use($messageId, $queueId) {
            $key = RedisKey::getFailedList($queueId);
            return $redis->lrem($key, $messageId, 1);
        });
    }

    /**
     * 获取队列失败消息数量
     *
     * @param string $queueId
     * @return int
     */
    public static function getFailedMessageCount($queueId)
    {
        return (int)PoolManager::use('redis', function($resource, RedisHandler $redis) use($queueId) {
            $key = RedisKey::getFailedList($queueId);
            return $redis->llen($key);
        });
    }

}