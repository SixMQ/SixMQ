<?php
namespace SixMQ\Logic;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use Imi\Redis\RedisHandler;

abstract class MessageWorkingLogic
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

    /**
     * 获取队列满足时间条件的消息ID列表
     *
     * @param string $queueId
     * @param integer $time
     * @param integer $begin
     * @param integer $limit
     * @return array
     */
    public static function getList($queueId, $time, $begin = 0, $limit = 1000)
    {
        return PoolManager::use('redis', function($resource, $redis) use($queueId, $time, $begin, $limit){
            return $redis->zrevrangebyscore(RedisKey::getWorkingMessageSet($queueId), $time, 0, [
                'limit'     =>  [$begin, $limit],
            ]);
        });
    }

    /**
     * 获取队列长度
     *
     * @param string $queueId
     * @return int
     */
    public static function count($queueId)
    {
        return PoolManager::use('redis', function($resource, RedisHandler $redis) use($queueId){
            return $redis->zCard(RedisKey::getWorkingMessageSet($queueId));
        });
    }

}