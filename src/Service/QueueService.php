<?php
namespace SixMQ\Service;

use Imi\Config;
use Imi\ServerManage;
use Imi\ConnectContext;
use Imi\RequestContext;
use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use SixMQ\Util\GenerateID;
use SixMQ\Util\QueueError;
use SixMQ\Struct\Queue\Message;
use SixMQ\Util\QueueCollection;
use SixMQ\Struct\Queue\Server\Pop;
use SixMQ\Struct\Queue\Server\Push;
use SixMQ\Util\QueuePopBlockParser;
use SixMQ\Struct\Queue\Server\Reply;
use SixMQ\Util\QueuePushBlockParser;
use Imi\Util\CoroutineChannelManager;

abstract class QueueService
{
    /**
     * 消息入队列
     *
     * @param \SixMQ\Struct\Queue\Client\Push $data
     * @return \SixMQ\Struct\Queue\Server\Reply|null
     */
    public static function push($data)
    {
        $messageId = null;
        $result = PoolManager::use('redis', function($resource, $redis) use($data, &$messageId){
            // 生成消息ID
            $messageId = GenerateID::get();
            $messageIdKey = RedisKey::getMessageId($messageId);
            // 开启事务
            $redis->multi();
            // 保存消息
            $message = new Message($data->data, $messageId);
            $message->queueId = $data->queueId;
            $message->retry = $data->retry;
            $message->timeout = $data->timeout;
            $message->delay = $data->delay;
            $isDelay = $data->delay > 0;
            if($isDelay)
            {
                $message->delayRunTime = $message->inTime + $data->delay;
            }
            // 消息存储
            $redis->set($messageIdKey, $message);
            if($isDelay)
            {
                // 加入延迟集合
                $redis->zadd(RedisKey::getDelaySet(), $message->delayRunTime, $messageId);
            }
            else
            {
                // 加入消息队列
                $redis->rpush(RedisKey::getMessageQueue($data->queueId), $messageId);
                // 加入超时队列
                if($data->timeout > -1)
                {
                    $redis->zadd(RedisKey::getQueueExpireSet($data->queueId), microtime(true) + $data->timeout, $messageId);
                }
            }
            // 运行事务
            return $redis->exec();
        });
        $success = null !== $result;
        $return = new Push($success);
        $return->queueId = $data->queueId;
        $return->messageId = $messageId;
        if($success && 0 !== $data->block)
        {
            $fd = RequestContext::get('fd');
            // 加入到 push block 中
            QueuePushBlockParser::add($fd, $data, $return);
            $return = null;
        }
        go(function() use($success, $data){
            // 队列记录
            if($success && !QueueCollection::has($data->queueId))
            {
                QueueCollection::append($data->queueId);
            }
            if($data->delay <= 0)
            {
                static::parsePopBlock($data->queueId);
            }
        });
        return $return;
    }

    /**
     * 消息出队列
     *
     * @param \SixMQ\Struct\Queue\Client\Pop $data
     * @param \Redis|\Swoole\Coroutine\Redis $redis
     * @return \SixMQ\Struct\Queue\Server\Pop|null
     */
    public static function pop($data, $redis = null)
    {
        $result = static::tryPop($data, $messageId, $message, $redis);

        if(!$result && 0 !== $data->block)
        {
            $return = new Pop($result);
            $fd = RequestContext::get('fd');
            QueuePopBlockParser::add($fd, $data);
            return null;
        }
        $return = new Pop($result);
        if($result)
        {
            $return->queueId = $data->queueId;
            $return->messageId = $messageId;
            $return->data = $message;
        }
        return $return;
    }

    /**
     * 尝试消息出队列
     *
     * @param \SixMQ\Struct\Queue\Client\Pop $data
     * @param string $messageId
     * @param string $message
     * @param \Redis|\Swoole\Coroutine\Redis $redis
     * @return boolean
     */
    private static function tryPop($data, &$messageId, &$message, $redis = null)
    {
        $func = function($resource, $redis) use($data, &$messageId, &$message){
            // 取出消息ID
            $messageId = $redis->lpop(RedisKey::getMessageQueue($data->queueId));
            if(!$messageId)
            {
                return false;
            }
            // 取出消息
            $message = $redis->get(RedisKey::getMessageId($messageId));
            // 消息超时判断
            if(!$message || ($message->timeout > -1 && $message->inTime + $message->timeout <= microtime(true)))
            {
                return false;
            }
            // 消息处理最大超时时间
            $expireTime = microtime(true) + $data->maxExpire;
            // 加入工作集合
            $redis->zadd(RedisKey::getWorkingMessageSet($data->queueId), $expireTime, $messageId);
            return true;
        };
        if(null === $redis)
        {
            return PoolManager::use('redis', $func);
        }
        else
        {
            return $func(null, $redis);
        }
    }

    /**
     * 任务超时处理
     *
     * @param string $queueId
     * @param string $messageId
     * @return void
     */
    public static function expireTask($queueId, $messageId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId){
            $workingMessageSetKey = RedisKey::getWorkingMessageSet($queueId);

            // 消息执行超时
            $message = $redis->get(RedisKey::getMessageId($messageId));

            // 移出工作集合
            $redis->zrem($workingMessageSetKey, $messageId);

            $message->success = false;
            $message->resultData = 'task timeout';
            $message->consum = false;
            // 设置消息数据
            $redis->set(RedisKey::getMessageId($messageId), $message);

            // 失败重试次数限制
            if(QueueError::inc($messageId) < $message->retry)
            {
                // 加入队列
                $redis->rpush(RedisKey::getMessageQueue($queueId), $messageId);
                $message->inTime = microtime(true);
                go(function() use($queueId){
                    static::parsePopBlock($queueId);
                });
            }
        });
        // 处理push阻塞推送
        static::parsePushBlock($messageId);
    }

    /**
     * 队列回滚
     *
     * @param string $queueId
     * @param string $messageId
     * @return void
     */
    public static function rollbackPop($queueId, $messageId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId){
            $workingMessageSetKey = RedisKey::getWorkingMessageSet($queueId);

            // 移出工作集合
            $redis->zrem($workingMessageSetKey, $messageId);

            // 加入队列
            $redis->lpush(RedisKey::getMessageQueue($queueId), $messageId);
        });
        go(function() use($queueId){
            static::parsePopBlock($queueId);
        });
    }

    /**
     * 消息处理完成
     *
     * @param \SixMQ\Struct\Queue\Client\Complete $data
     * @return void
     */
    public static function complete($data)
    {
        $result = PoolManager::use('redis', function($resource, $redis) use($data){
            // 取出消息数据
            $message = $redis->get(RedisKey::getMessageId($data->messageId));

            if(!$message)
            {
                return null;
            }
            // 开启事务
            $redis->multi();

            // 移出集合队列
            $redis->zrem(RedisKey::getWorkingMessageSet($data->queueId), $data->messageId);

            // 移除队列（先触发了失败重新入队，尝试出列）
            $redis->lrem(RedisKey::getMessageQueue($data->queueId), $data->messageId, 1);

            $message->consum = true;
            $message->success = $data->success;
            $message->resultData = $data->data;

            // 设置消息数据
            $redis->set(RedisKey::getMessageId($data->messageId), $message);

            // 运行事务
            return $redis->exec();
        });
        $return = new Reply(null !== $result);
        // 处理push阻塞推送
        static::parsePushBlock($data->messageId);
        return $return;
    }

    /**
     * 获取消息数据
     *
     * @param string $messageId
     * @return \SixMQ\Struct\Queue\Message|boolean
     */
    public static function getMessage($messageId)
    {
        $result = PoolManager::use('redis', function($resource, $redis) use($messageId){
            return $redis->get(RedisKey::getMessageId($messageId));
        });
        return $result;
    }

    /**
     * 将消息移出队列
     *
     * @param string $messageId
     * @return \SixMQ\Struct\Queue\Server\Reply
     */
    public static function remove($messageId)
    {
        $message = static::getMessage($messageId);
        $result = PoolManager::use('redis', function($resource, $redis) use($messageId, $message){
            // 移出队列
            return $redis->lrem(RedisKey::getMessageQueue($message->queueId), $message->messageId, 1);
        });
        $return = new Reply(!!$result);
        return $return;
    }

    /**
     * 处理push阻塞推送
     *
     * @param string $messageId
     * @return \SixMQ\Struct\Queue\Message
     */
    private static function parsePushBlock($messageId)
    {
        if(RequestContext::exsits())
        {
            $server = RequestContext::get('server');
        }
        else
        {
            $server = ServerManage::getServer('MQService');
        }
        // 处理push阻塞推送
        go(function() use($messageId, $server){
            RequestContext::create();
            RequestContext::set('server', $server);
            QueuePushBlockParser::complete($messageId);
            RequestContext::destroy();
        });
    }

    /**
     * 处理pop阻塞推送
     *
     * @param string $queueId
     * @return void
     */
    private static function parsePopBlock($queueId)
    {
        if(RequestContext::exsits())
        {
            $server = RequestContext::get('server');
        }
        else
        {
            $server = ServerManage::getServer('MQService');
        }
        // 处理pop阻塞推送
        go(function() use($queueId, $server){
            RequestContext::create();
            RequestContext::set('server', $server);
            QueuePopBlockParser::complete($queueId);
            RequestContext::destroy();
        });
    }

    /**
     * 消息超时处理
     *
     * @param string $queueId
     * @param string $messageId
     * @return void
     */
    public static function expireMessage($queueId, $messageId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $messageId){
            // 移出队列
            $redis->lrem(RedisKey::getMessageQueue($queueId), $messageId, 1);

            $expireMessageSetKey = RedisKey::getQueueExpireSet($queueId);

            // 消息执行超时
            $message = $redis->get(RedisKey::getMessageId($messageId));

            // 移出工作集合
            $redis->zrem($expireMessageSetKey, $messageId);

            $message->success = false;
            $message->resultData = 'message timeout';

            // 设置消息数据
            $redis->set(RedisKey::getMessageId($messageId), $message);
        });
        // 处理push阻塞推送
        static::parsePushBlock($messageId);
    }

    /**
     * 延迟消息进队列
     *
     * @param string $messageId
     * @return void
     */
    public static function delayToQueue($messageId)
    {
        PoolManager::use('redis', function($resource, $redis) use($messageId){
            // 获取消息
            $message = static::getMessage($messageId);

            // 移出延时队列
            $redis->zrem(RedisKey::getDelaySet(), $messageId);

            // 加入消息队列
            $redis->rpush(RedisKey::getMessageQueue($message->queueId), $messageId);

            // 加入超时队列
            if($message->timeout > -1)
            {
                $redis->zadd(RedisKey::getQueueExpireSet($message->queueId), microtime(true) + $message->timeout, $messageId);
            }

            go(function() use($message){
                static::parsePopBlock($message->queueId);
            });
        });
    }
}