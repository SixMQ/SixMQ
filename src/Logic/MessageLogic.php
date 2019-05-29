<?php
namespace SixMQ\Logic;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use Imi\Redis\RedisHandler;
use SixMQ\Struct\Queue\Message;
use SixMQ\Struct\Queue\GroupMessageStatus;
use SixMQ\Service\QueueService;
use SixMQ\Struct\Util\MessageStatus;
use Imi\Event\Event;
use Imi\Util\Text;

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
        $key = RedisKey::getMessageId($messageId);
        $data = $message->toArray();
        PoolManager::use('redis', function($resource, RedisHandler $redis) use($key, $data, $ttl){
            $redis->multi();
            $redis->del($key);
            $redis->hMset($key, $data);
            if($ttl > 0)
            {
                $redis->expire($key, $ttl);
            }
            $redis->exec();
        });
        Event::trigger('SIXMQ.MESSAGE.CHANGE_STATUS', [
            'message'   =>  $message,
        ]);
    }

    /**
     * 获取
     *
     * @param string $messageId
     * @return \SixMQ\Struct\Queue\Message|null
     */
    public static function get($messageId)
    {
        $key = RedisKey::getMessageId($messageId);
        $data = PoolManager::use('redis', function($resource, RedisHandler $redis) use($key){
            return $redis->hGetAll($key);
        });
        return Message::loadFromStore($data);
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
        $list = PoolManager::use('redis', function($resource, RedisHandler $redis) use($keys){
            $list = [];
            foreach($keys as $key)
            {
                $list[] = $redis->hGetAll($key);
            }
            return $list;
        });
        $result = [];
        foreach($list as $item)
        {
            $result[] = Message::loadFromStore($item);
        }
        return $result;
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
        $message->status = MessageStatus::FREE;
        static::set($message->messageId, $message);
        $canNotifyPop = false;
        if(Text::isEmpty($message->groupId))
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
        else
        {
            // 有分组，加入分组集合
            MessageGroupLogic::setMessageStatus($message->queueId, $message->groupId, $messageId, GroupMessageStatus::FREE);
            MessageGroupLogic::addWorkingGroup($message->queueId, $message->groupId);
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

    /**
     * 获取消息过期时间
     *
     * @param string $messageId
     * @return void
     */
    public static function getTTL($messageId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($messageId){
            return $redis->ttl(RedisKey::getMessageId($messageId));
        });
    }

}