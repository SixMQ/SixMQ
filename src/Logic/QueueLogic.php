<?php
namespace SixMQ\Logic;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use Imi\Redis\RedisHandler;
use Imi\Util\Pagination;

/**
 * 队列逻辑
 */
abstract class QueueLogic
{
    /**
     * 加入队列首位
     *
     * @param string $queueId
     * @param string $messageId
     * @return void
     */
    public static function lpush($queueId, $messageId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId){
            $redis->lpush(RedisKey::getMessageQueue($queueId), $messageId);
        });
    }

    /**
     * 加入队列末尾
     *
     * @param string $queueId
     * @param string $messageId
     * @return void
     */
    public static function rpush($queueId, $messageId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId){
            $redis->rpush(RedisKey::getMessageQueue($queueId), $messageId);
        });
    }

    /**
     * 加入所有消息队列
     *
     * @param string $queueId
     * @param string $messageId
     * @return void
     */
    public static function pushToAll($queueId, $messageId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId){
            $redis->rpush(RedisKey::getQueueAll($queueId), $messageId);
        });
    }

    /**
     * 移出所有消息队列
     *
     * @param string $queueId
     * @param string $messageId
     * @return void
     */
    public static function removeFromAll($queueId, $messageId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId){
            $redis->lrem(RedisKey::getQueueAll($queueId), $messageId, 1);
        });
    }

    /**
     * 队首出队列
     *
     * @param string $queueId
     * @return string
     */
    public static function lpop($queueId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($queueId){
            return $redis->lpop(RedisKey::getMessageQueue($queueId));
        });
    }

    /**
     * 队尾出队列
     *
     * @param string $queueId
     * @return string
     */
    public static function rpop($queueId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($queueId){
            return $redis->rpop(RedisKey::getMessageQueue($queueId));
        });
    }

    /**
     * 将消息ID移出队列
     *
     * @param string $queueId
     * @param string $messageId
     * @return void
     */
    public static function remove($queueId, $messageId, $removeFromAll)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId, $removeFromAll){
            $redis->lrem(RedisKey::getMessageQueue($queueId), $messageId, 1);
            if($removeFromAll)
            {
                $redis->lrem(RedisKey::getQueueAll($queueId), $messageId, 1);
            }
        });
    }

    /**
     * 获取所有队列的ID
     *
     * @return string[]
     */
    public static function getList()
    {
        return PoolManager::use('redis', function($resource, $redis) {
            return $redis->smembers(RedisKey::getQueueList());
        });
    }

    /**
     * 队列是否存在
     *
     * @param string $queueId
     * @return boolean
     */
    public static function has($queueId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($queueId) {
            return $redis->sIsMember(RedisKey::getQueueList(), $queueId);
        });
    }

    /**
     * 增加队列
     *
     * @param string $queueId
     * @return boolean
     */
    public static function append($queueId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($queueId) {
            return $redis->sadd(RedisKey::getQueueList(), $queueId);
        }) > 0;
    }

    /**
     * 获取队列消息数量
     *
     * @param string $queueId
     * @return int
     */
    public static function count($queueId)
    {
        return (int)PoolManager::use('redis', function($resource, RedisHandler $redis) use($queueId) {
            return $redis->lLen(RedisKey::getMessageQueue($queueId));
        });
    }

    /**
     * 获取队列历史消息总数
     *
     * @param string $queueId
     * @return int
     */
    public static function allCount($queueId)
    {
        return (int)PoolManager::use('redis', function($resource, RedisHandler $redis) use($queueId) {
            return $redis->lLen(RedisKey::getQueueAll($queueId));
        });
    }

    /**
     * 查询消息队列中消息ID列表
     *
     * @param string $queueId
     * @param int $page
     * @param int $count
     * @param int $pages
     * @return string[]
     */
    public static function selectMessageIds($queueId, $page, $count, &$pages)
    {
        $pagination = new Pagination($page, $count);

        $key = RedisKey::getMessageQueue($queueId);
        
        $list = PoolManager::use('redis', function($resource, RedisHandler $redis) use($key, $pagination) {
            return $redis->lrange($key, $pagination->getLimitOffset(), $pagination->getLimitEndOffset());
        });;

        $records = QueueLogic::count($queueId);
        $pages = $pagination->calcPageCount($records);
        return $list;
    }

    /**
     * 查询消息队列中消息ID列表
     *
     * @param string $queueId
     * @param int $page
     * @param int $count
     * @param int $pages
     * @return string[]
     */
    public static function selectAllMessageIds($queueId, $page, $count, &$pages)
    {
        $pagination = new Pagination($page, $count);

        $key = RedisKey::getQueueAll($queueId);
        
        $list = PoolManager::use('redis', function($resource, RedisHandler $redis) use($key, $pagination) {
            return $redis->lrange($key, $pagination->getLimitOffset(), $pagination->getLimitEndOffset());
        });;

        $records = QueueLogic::allCount($queueId);
        $pages = $pagination->calcPageCount($records);
        return $list;
    }
}