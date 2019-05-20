<?php
namespace SixMQ\Logic;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use Imi\Redis\RedisHandler;
use SixMQ\Struct\Queue\Message;
use SixMQ\Struct\Queue\GroupMessageStatus;
use SixMQ\Service\QueueService;

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
     * 查询多条
     *
     * @param string $messageIds
     * @return \SixMQ\Struct\Queue\Message[]
     */
    public static function select($messageIds)
    {
        $keys = [];
        foreach($messageIds as $messageId)
        {
            $keys[] = RedisKey::getMessageId($messageId);
        }
        if(!$keys)
        {
            return [];
        }
        return PoolManager::use('redis', function($resource, $redis) use($keys){
            return $redis->mget($keys);
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

    /**
     * 重新推送队列
     *
     * @param string $messageId
     * @return void
     */
    public static function repush($messageId)
    {
        $message = static::get($messageId);
        if(!$message)
        {
            throw new \RuntimeException('消息不存在');
        }
        if(null !== $message->groupId)
        {
            // 有分组，加入分组集合
            MessageGroupLogic::setMessageStatus($message->queueId, $message->groupId, $messageId, GroupMessageStatus::FREE);
            MessageGroupLogic::addWorkingGroup($message->queueId, $message->groupId);
        }
        else
        {
            $canNotifyPop = true;
            // 加入超时队列
            if($message->timeout > -1)
            {
                TimeoutLogic::push($message->queueId, $messageId, microtime(true) + $message->timeout);
            }
            // 加入消息队列
            QueueLogic::rpush($message->queueId, $messageId);
        }
        // 队列记录
        if(!QueueLogic::has($message->queueId))
        {
            QueueLogic::append($message->queueId);
        }
        if($canNotifyPop)
        {
            QueueService::parsePopBlock($message->queueId);
        }
    }
}