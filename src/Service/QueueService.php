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
use SixMQ\Logic\MessageLogic;
use SixMQ\Struct\Queue\Message;
use SixMQ\Logic\DelayLogic;
use SixMQ\Logic\QueueLogic;
use SixMQ\Logic\MessageWorkingLogic;
use SixMQ\Struct\Queue\Server\Pop;
use SixMQ\Struct\Queue\Server\Push;
use SixMQ\Logic\QueuePopBlockLogic;
use SixMQ\Struct\Queue\Server\Reply;
use SixMQ\Logic\QueuePushBlockLogic;
use Imi\Util\CoroutineChannelManager;
use SixMQ\Logic\MessageGroupLogic;
use SixMQ\Struct\Queue\GroupMessageStatus;
use SixMQ\Logic\TimeoutLogic;

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
        $canNotifyPop = null;
        $canNotifyPop = false;
        // 生成消息ID
        $messageId = GenerateID::get();
        // 保存消息
        $message = new Message($data->data, $messageId);
        $message->queueId = $data->queueId;
        $message->retry = $data->retry;
        $message->timeout = $data->timeout;
        $message->delay = $data->delay;
        $message->groupId = $data->groupId;
        $isDelay = $data->delay > 0;
        if($isDelay)
        {
            $message->delayRunTime = $message->inTime + $data->delay;
        }
        // 消息存储
        MessageLogic::set($messageId, $message);
        if(null !== $message->groupId)
        {
            // 有分组，加入分组集合
            MessageGroupLogic::setMessageStatus($data->queueId, $data->groupId, $messageId, GroupMessageStatus::FREE);
            MessageGroupLogic::addWorkingGroup($data->queueId, $data->groupId);
        }
        else if($isDelay)
        {
            // 加入延迟集合
            DelayLogic::add($messageId, $message->delayRunTime);
        }
        else
        {
            $canNotifyPop = true;
            // 加入超时队列
            if($data->timeout > -1)
            {
                TimeoutLogic::push($data->queueId, $messageId, microtime(true) + $data->timeout);
            }
            // 加入消息队列
            QueueLogic::rpush($data->queueId, $messageId);
        }
        // TODO:
        $result = true;
        $success = null !== $result;
        $return = new Push($success);
        $return->queueId = $data->queueId;
        $return->messageId = $messageId;
        if($success && 0 !== $data->block)
        {
            $fd = RequestContext::get('fd');
            // 加入到 push block 中
            QueuePushBlockLogic::add($fd, $data, $return);
            $return = null;
        }
        go(function() use($success, $data, $canNotifyPop){
            // 队列记录
            if($success && !QueueLogic::has($data->queueId))
            {
                QueueLogic::append($data->queueId);
            }
            if($canNotifyPop)
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
            QueuePopBlockLogic::add($fd, $data);
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
            $messageId = QueueLogic::lpop($data->queueId);
            if(!$messageId)
            {
                return false;
            }
            // 取出消息
            $message = MessageLogic::get($messageId);
            // 消息超时判断
            if(!$message || ($message->timeout > -1 && $message->inTime + $message->timeout <= microtime(true)))
            {
                return false;
            }
            // 消息处理最大超时时间
            $expireTime = microtime(true) + $data->maxExpire;
            // 加入工作集合
            MessageWorkingLogic::add($data->queueId, $messageId, $expireTime);
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
        // 消息执行超时
        $message = MessageLogic::get($messageId);

        // 移出工作集合
        MessageWorkingLogic::remove($queueId, $messageId);

        $message->success = false;
        $message->resultData = 'task timeout';
        $message->consum = false;
        // 设置消息数据
        MessageLogic::set($messageId, $message);

        // 失败重试次数限制
        if(QueueError::inc($messageId) < $message->retry)
        {
            // 加入队列
            QueueLogic::rpush($queueId, $messageId);
            $message->inTime = microtime(true);
            go(function() use($queueId){
                static::parsePopBlock($queueId);
            });
        }
        else if(null !== $message->groupId)
        {
            MessageGroupLogic::setMessageStatus($message->queueId, $message->groupId, $message->messageId, GroupMessageStatus::COMPLETE);
            MessageGroupLogic::setWorkingGroupMessage($message->queueId, $message->groupId, '');
        }
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
        // 移出工作集合
        MessageWorkingLogic::remove($queueId, $messageId);

        // 加入队列
        QueueLogic::lpush($queueId, $messageId);
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
        // 取出消息数据
        $message = MessageLogic::get($data->messageId);

        if(!$message)
        {
            return null;
        }

        // 移出集合队列
        MessageWorkingLogic::remove($data->queueId, $data->messageId);

        // 移除队列（先触发了失败重新入队，尝试出列）
        
        QueueLogic::remove($data->queueId, $data->messageId);

        $message->consum = true;
        $message->success = $data->success;
        $message->resultData = $data->data;

        // 设置消息数据
        MessageLogic::set($data->messageId, $message);

        if(null !== $message->groupId)
        {
            // 分组正在执行的任务置空
            MessageGroupLogic::setMessageStatus($message->queueId, $message->groupId, $message->messageId, GroupMessageStatus::COMPLETE);
            MessageGroupLogic::setWorkingGroupMessage($message->queueId, $message->groupId, '');
        }

        // TODO:
        $result = true;
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
        return MessageLogic::get($messageId);
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
        if($message)
        {
            // 移出分组
            if(null !== $message->groupId)
            {
                MessageGroupLogic::setMessageStatus($message->queueId, $message->groupId, $message->messageId, GroupMessageStatus::CANCEL);
                if($message->messageId === MessageGroupLogic::getWorkingMessage($message->queueId, $message->groupId))
                {
                    MessageGroupLogic::setWorkingGroupMessage($message->queueId, $message->groupId, '');
                }
            }
            // 移出延时队列
            if($message->delay > 0)
            {
                DelayLogic::remove($message->messageId);
            }
            // 移出队列
            QueueLogic::remove($message->queueId, $message->messageId);
            $result = true;
        }
        else
        {
            $result = false;
        }
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
            QueuePushBlockLogic::complete($messageId);
            RequestContext::destroy();
        });
    }

    /**
     * 处理pop阻塞推送
     *
     * @param string $queueId
     * @return void
     */
    public static function parsePopBlock($queueId)
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
            QueuePopBlockLogic::complete($queueId);
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
        // 移出队列
        QueueLogic::remove($queueId, $messageId);

        // 消息执行超时
        $message = MessageLogic::get($messageId);

        // 移出超时工作集合
        TimeoutLogic::remove($queueId, $messageId);

        $message->success = false;
        $message->resultData = 'message timeout';

        // 设置消息数据
        MessageLogic::set($messageId, $message);
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
        // 获取消息
        $message = static::getMessage($messageId);

        // 移出延时队列
        DelayLogic::remove($messageId);

        // 加入消息队列
        QueueLogic::rpush($message->queueId, $messageId);

        // 加入超时队列
        if($message->timeout > -1)
        {
            TimeoutLogic::push($message->queueId, $messageId, microtime(true) + $message->timeout);
        }

        go(function() use($message){
            static::parsePopBlock($message->queueId);
        });
    }
}