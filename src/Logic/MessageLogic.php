<?php
namespace SixMQ\Logic;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use SixMQ\Struct\Queue\Message;
use Imi\Redis\RedisHandler;

/**
 * 消息逻辑
 */
abstract class MessageLogic
{
    /**
     * 设置
     *
     * @param string $messageId
     * @param Message $message
     * @param int|null $ttl
     * @return void
     */
    public static function set($messageId, Message $message, $ttl = null)
    {
        PoolManager::use('redis', function($resource, RedisHandler $redis) use($messageId, $message, $ttl){
            $redis->set(RedisKey::getMessageId($messageId), $message, $ttl);
        });
    }

    /**
     * 获取
     *
     * @param string $messageId
     * @return \SixMQ\Struct\Queue\Message|null
     */
    public static function get($messageId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($messageId){
            return $redis->get(RedisKey::getMessageId($messageId));
        });
    }

    /**
     * 移除消息
     *
     * @param string $messageId
     * @return void
     */
    public static function removeMessage($messageId)
    {
        PoolManager::use('redis', function($resource, $redis) use($messageId){
            return $redis->del(RedisKey::getMessageId($messageId));
        });
    }

}