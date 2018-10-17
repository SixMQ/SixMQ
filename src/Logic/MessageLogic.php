<?php
namespace SixMQ\Logic;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use SixMQ\Struct\Queue\Message;

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
     * @return void
     */
    public static function set($messageId, Message $message)
    {
        PoolManager::use('redis', function($resource, $redis) use($messageId, $message){
            $redis->set(RedisKey::getMessageId($messageId), $message);
        });
    }

    /**
     * 获取
     *
     * @param string $messageId
     * @return SixMQ\Struct\Queue\Message
     */
    public static function get($messageId): Message
    {
        return PoolManager::use('redis', function($resource, $redis) use($messageId, $message){
            return $redis->get(RedisKey::getMessageId($messageId), $message);
        });
    }
}