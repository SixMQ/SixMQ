<?php
namespace SixMQ\Logic;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;

/**
 * 延迟集合
 */
abstract class DelayLogic
{
    /**
     * 添加消息进延迟集合
     *
     * @param stirng $messageId
     * @param int $delayRunTime
     * @return void
     */
    public static function add($messageId, $delayRunTime)
    {
        return PoolManager::use('redis', function($resource, $redis) use($messageId, $delayRunTime){
            return $redis->zadd(RedisKey::getDelaySet(), $message->delayRunTime, $messageId);
        });
    }

    /**
     * 将消息移出延迟集合
     *
     * @param string $messageId
     * @return void
     */
    public static function remove($messageId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($messageId){
            return $redis->zrem(RedisKey::getDelaySet(), $messageId);
        });
    }

    /**
     * 遍历满足时间的延迟消息ID列表
     *
     * @param int $time
     * @param int $begin
     * @param int $limit
     * @return array
     */
    public static function getList($time, $begin = 0, $limit = 1000)
    {
        return PoolManager::use('redis', function($resource, $redis) use($time, $begin, $limit){
            return $redis->zrevrangebyscore(RedisKey::getDelaySet(), $time, 0, [
                'limit'     =>  [$begin, $limit],
            ]);
        });
    }
}