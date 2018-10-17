<?php
namespace SixMQ\Logic;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;

/**
 * 超时逻辑
 */
abstract class TimeoutLogic
{
    /**
     * 加入超时队列
     *
     * @param string $queueId
     * @param string $messageId
     * @param int $timeout
     * @return void
     */
    public static function push($queueId, $messageId, $timeout)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId, $timeout){
            $redis->zadd(RedisKey::getQueueExpireSet($queueId), $timeout, $messageId);
        });
    }

    /**
     * 移出超时队列
     *
     * @param string $queueId
     * @param string $messageId
     * @return void
     */
    public static function remove($queueId, $messageId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId){
            $redis->zrem(RedisKey::getQueueExpireSet($queueId), $messageId);
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
            return $redis->zrevrangebyscore(RedisKey::getQueueExpireSet($queueId), $time, 0, [
                'limit'     =>  [$begin, $limit],
            ]);
        });
    }
}