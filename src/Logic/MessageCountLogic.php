<?php
namespace SixMQ\Logic;

use Imi\Util\Pagination;
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
        $key = RedisKey::getFailedList($queueId);
        $countKey = RedisKey::getFailedCount();
        PoolManager::use('redis', function($resource, RedisHandler $redis) use($messageId, $queueId, $key, $countKey) {
            $redis->multi();
            $redis->lpush($key, $messageId);
            $redis->hIncrBy($countKey, $queueId, 1);
            $redis->exec();
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
        PoolManager::use('redis', function($resource, RedisHandler $redis) use($messageId, $queueId) {
            $key = RedisKey::getFailedList($queueId);
            $redis->lrem($key, $messageId, 1);
        });
    }

    /**
     * 获取队列失败消息数量
     *
     * @param string $queueId
     * @return int
     */
    public static function getFailedMessageRealCount($queueId)
    {
        return (int)PoolManager::use('redis', function($resource, RedisHandler $redis) use($queueId) {
            $key = RedisKey::getFailedList($queueId);
            return $redis->llen($key);
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
        $key = RedisKey::getFailedCount();
        return (int)PoolManager::use('redis', function($resource, RedisHandler $redis) use($queueId, $key) {
            return $redis->hGet($key, $queueId);
        });
    }

    /**
     * 查询失败消息队列中消息ID列表
     *
     * @param string $queueId
     * @param int $page
     * @param int $count
     * @param int $pages
     * @return string[]
     */
    public static function selectFailMessageIds($queueId, $page, $count, &$pages)
    {
        $pagination = new Pagination($page, $count);

        $key = RedisKey::getFailedList($queueId);
        
        $list = PoolManager::use('redis', function($resource, RedisHandler $redis) use($key, $pagination) {
            return $redis->lrange($key, $pagination->getLimitOffset(), $pagination->getLimitEndOffset());
        });

        $records = static::getFailedMessageRealCount($queueId);
        $pages = $pagination->calcPageCount($records);
        return $list;
    }

    /**
     * 增加统计成功消息
     *
     * @param string $messageId
     * @param string $queueId
     * @return void
     */
    public static function addSuccessMessage($messageId, $queueId)
    {
        $key = RedisKey::getSuccessList($queueId);
        $countKey = RedisKey::getSuccessCount();
        PoolManager::use('redis', function($resource, RedisHandler $redis) use($messageId, $queueId, $key, $countKey) {
            $redis->multi();
            $redis->lpush($key, $messageId);
            $redis->hIncrBy($countKey, $queueId, 1);
            $redis->exec();
        });
    }

    /**
     * 移除统计成功消息
     *
     * @param string $messageId
     * @param string $queueId
     * @return void
     */
    public static function removeSuccessMessage($messageId, $queueId)
    {
        return PoolManager::use('redis', function($resource, RedisHandler $redis) use($messageId, $queueId) {
            $key = RedisKey::getSuccessList($queueId);
            var_dump($key, $messageId, 1);
            return $redis->lrem($key, $messageId, 1);
        });
    }

    /**
     * 获取队列成功消息数量
     *
     * @param string $queueId
     * @return int
     */
    public static function getSuccessMessageRealCount($queueId)
    {
        return (int)PoolManager::use('redis', function($resource, RedisHandler $redis) use($queueId) {
            $key = RedisKey::getSuccessList($queueId);
            return $redis->llen($key);
        });
    }

    /**
     * 获取队列成功消息数量
     *
     * @param string $queueId
     * @return int
     */
    public static function getSuccessMessageCount($queueId)
    {
        $key = RedisKey::getSuccessCount();
        return (int)PoolManager::use('redis', function($resource, RedisHandler $redis) use($queueId, $key) {
            return $redis->hGet($key, $queueId);
        });
    }

    /**
     * 查询成功消息队列中消息ID列表
     *
     * @param string $queueId
     * @param int $page
     * @param int $count
     * @param int $pages
     * @return string[]
     */
    public static function selectSuccessMessageIds($queueId, $page, $count, &$pages)
    {
        $pagination = new Pagination($page, $count);

        $key = RedisKey::getSuccessList($queueId);
        
        $list = PoolManager::use('redis', function($resource, RedisHandler $redis) use($key, $pagination) {
            return $redis->lrange($key, $pagination->getLimitOffset(), $pagination->getLimitEndOffset());
        });

        $records = static::getSuccessMessageRealCount($queueId);
        $pages = $pagination->calcPageCount($records);
        return $list;
    }

    /**
     * 增加统计超时消息
     *
     * @param string $messageId
     * @param string $queueId
     * @return void
     */
    public static function addTimeoutMessage($messageId, $queueId)
    {
        $key = RedisKey::getTimeoutList($queueId);
        $countKey = RedisKey::getTimeoutCount();
        PoolManager::use('redis', function($resource, RedisHandler $redis) use($messageId, $queueId, $key, $countKey) {
            $redis->multi();
            $redis->lpush($key, $messageId);
            $redis->hIncrBy($countKey, $queueId, 1);
            $redis->exec();
        });
    }

    /**
     * 移除统计超时消息
     *
     * @param string $messageId
     * @param string $queueId
     * @return void
     */
    public static function removeTimeoutMessage($messageId, $queueId)
    {
        return PoolManager::use('redis', function($resource, RedisHandler $redis) use($messageId, $queueId) {
            $key = RedisKey::getTimeoutList($queueId);
            return $redis->lrem($key, $messageId, 1);
        });
    }

    /**
     * 获取队列超时消息数量
     *
     * @param string $queueId
     * @return int
     */
    public static function getTimeoutMessageRealCount($queueId)
    {
        return (int)PoolManager::use('redis', function($resource, RedisHandler $redis) use($queueId) {
            $key = RedisKey::getTimeoutList($queueId);
            return $redis->llen($key);
        });
    }

    /**
     * 获取队列超时消息数量
     *
     * @param string $queueId
     * @return int
     */
    public static function getTimeoutMessageCount($queueId)
    {
        $key = RedisKey::getTimeoutCount();
        return (int)PoolManager::use('redis', function($resource, RedisHandler $redis) use($queueId, $key) {
            return $redis->hGet($key, $queueId);
        });
    }

    /**
     * 查询超时消息队列中消息ID列表
     *
     * @param string $queueId
     * @param int $page
     * @param int $count
     * @param int $pages
     * @return string[]
     */
    public static function selectTimeoutMessageIds($queueId, $page, $count, &$pages)
    {
        $pagination = new Pagination($page, $count);

        $key = RedisKey::getTimeoutList($queueId);
        
        $list = PoolManager::use('redis', function($resource, RedisHandler $redis) use($key, $pagination) {
            return $redis->lrange($key, $pagination->getLimitOffset(), $pagination->getLimitEndOffset());
        });

        $records = static::getTimeoutMessageRealCount($queueId);
        $pages = $pagination->calcPageCount($records);
        return $list;
    }
}