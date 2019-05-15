<?php
namespace SixMQ\Logic;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use Imi\Redis\RedisHandler;

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
    public static function remove($queueId, $messageId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId){
            $redis->lrem(RedisKey::getMessageQueue($queueId), $messageId, 1);
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

}