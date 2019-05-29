<?php
namespace SixMQ\Logic;

use Imi\Pool\PoolManager;
use Imi\Redis\RedisHandler;
use SixMQ\Util\RedisKey;

abstract class MessageExpire
{
    /**
     * 增加消息过期处理
     *
     * @param string $messageId
     * @param string $queueId
     * @param int $ttl
     * @return void
     */
    public static function add($messageId, $queueId, $ttl)
    {
        $expireTime = time() + $ttl;
        $data = json_encode([
            'messageId'     =>  $messageId,
            'queueId'       =>  $queueId,
        ]);
        $key = RedisKey::getMessageExpire();
        PoolManager::use('redis', function($resource, RedisHandler $redis) use($messageId, $queueId, $ttl, $key, $expireTime, $data) {
            $redis->zAdd($key, $expireTime, $data);
        });
    }

    /**
     * 获取已过期
     *
     * @param int $count
     * @return string[]
     */
    public static function getExpireMessages($count)
    {
        $time = time();
        $key = RedisKey::getMessageExpire();
        $list = PoolManager::use('redis', function($resource, RedisHandler $redis) use($key, $time, $count) {
            return $redis->zRangeByScore($key, 0, $time, ['limit'=>[0, $count]]);
        });
        $result = [];
        if($list)
        {
            foreach($list as $data)
            {
                $result[] = json_decode($data, true);
            }
        }
        return $result;
    }

    /**
     * 移除消息
     *
     * @param array $data
     * @return void
     */
    public static function removeMessages(array $data)
    {
        $members = [];
        foreach($data as $item)
        {
            if(is_string($item))
            {
                $members[] = $item;
            }
            else
            {
                $members[] = json_encode($item);
            }
        }

        $key = RedisKey::getMessageExpire();
        PoolManager::use('redis', function($resource, RedisHandler $redis) use($key, $members) {
            $redis->zRem($key, ...$members);
        });
    }

}