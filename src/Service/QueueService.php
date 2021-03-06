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
use SixMQ\Logic\DelayLogic;
use SixMQ\Logic\QueueLogic;
use SixMQ\Logic\MessageLogic;
use SixMQ\Logic\TimeoutLogic;
use SixMQ\Struct\Queue\Message;
use SixMQ\Logic\MessageCountLogic;
use SixMQ\Logic\MessageGroupLogic;
use SixMQ\Struct\Queue\Server\Pop;
use SixMQ\Logic\QueuePopBlockLogic;
use SixMQ\Struct\Queue\Server\Push;
use SixMQ\Logic\MessageWorkingLogic;
use SixMQ\Logic\QueuePushBlockLogic;
use SixMQ\Struct\Queue\Server\Reply;
use Imi\Util\CoroutineChannelManager;
use SixMQ\Struct\Queue\GroupMessageStatus;
use SixMQ\Struct\Util\MessageStatus;
use Imi\Util\Text;
use SixMQ\Logic\MessageExpire;

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
        // 加入全部列表
        QueueLogic::pushToAll($message->queueId, $messageId);
        // 统计
        MessageCountLogic::incQueueMessage($message->queueId);
        if(!Text::isEmpty($message->groupId))
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
        // 队列记录
        if($success && !QueueLogic::has($data->queueId))
        {
            QueueLogic::append($data->queueId);
        }
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
            $return->queueId = $message->queueId;
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
     * @param \SixMQ\Struct\Queue\Message $message
     * @param \Redis|\Swoole\Coroutine\Redis $redis
     * @return boolean
     */
    private static function tryPop($data, &$messageId, &$message, $redis = null)
    {
        $func = function($resource, $redis) use($data, &$messageId, &$message){
            foreach(is_array($data->queueId) ? $data->queueId : [$data->queueId] as $queueId)
            {
                // 取出消息ID
                $messageId = QueueLogic::lpop($queueId);
                if(!$messageId)
                {
                    continue;
                }
                // 取出消息
                $message = MessageLogic::get($messageId);
                // 消息超时判断
                if(!$message || ($message->timeout > -1 && $message->inTime + $message->timeout <= microtime(true)))
                {
                    continue;
                }
                // 保存消息
                $message->status = MessageStatus::WORKING;
                MessageLogic::set($messageId, $message);
                // 消息处理最大超时时间
                $expireTime = microtime(true) + $data->maxExpire;
                // 加入工作集合
                MessageWorkingLogic::add($queueId, $messageId, $expireTime);
                return true;
            }
            return false;
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
        $message->consum = true;

        // 失败重试次数限制
        if(QueueError::inc($messageId) < $message->retry)
        {
            // 加入队列
            $message->status = MessageStatus::FREE;
            // 设置消息数据
            MessageLogic::set($messageId, $message);
            QueueLogic::rpush($queueId, $messageId);
            $message->inTime = microtime(true);
        }
        else
        {
            $message->status = MessageStatus::TIMEOUT;
            // 设置消息数据
            MessageLogic::set($messageId, $message);
            if(!Text::isEmpty($message->groupId))
            {
                MessageGroupLogic::setMessageStatus($message->queueId, $message->groupId, $message->messageId, GroupMessageStatus::COMPLETE);
                MessageGroupLogic::setWorkingGroupMessage($message->queueId, $message->groupId, '');
            }
        }
        // 处理push阻塞推送
        static::parsePushBlock($messageId);
    }

    /**
     * 队列回滚
     *
     * @param string $queueId
     * @param string $messageId
     * 
     * @return void
     */
    public static function rollbackPop($queueId, $messageId)
    {
        // 移出工作集合
        MessageWorkingLogic::remove($queueId, $messageId);

        // 改变状态
        $message = MessageLogic::get($messageId);
        $message->status = MessageStatus::FREE;
        MessageLogic::set($messageId, $message);

        // 加入队列
        QueueLogic::lpush($queueId, $messageId);

    }

    /**
     * 消息处理完成
     *
     * @param \SixMQ\Struct\Queue\Client\Complete $data
     * @return \SixMQ\Struct\Queue\Server\Reply|null
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
        MessageWorkingLogic::remove($message->queueId, $data->messageId);

        // 移除队列（先触发了失败重新入队，尝试出列）

        if($data->success && Config::get('@app.common.drop_message_when_complete'))
        {
            QueueLogic::remove($message->queueId, $data->messageId, true);
            MessageLogic::removeMessage($message->messageId);
        }
        else
        {
            QueueLogic::remove($message->queueId, $data->messageId, false);
            $message->consum = true;
            $message->success = $data->success;
            $message->resultData = $data->data;
            $message->status = $data->success ? MessageStatus::SUCCESS : MessageStatus::FAIL;
    
            // 设置消息数据
            if($data->success)
            {
                $ttl = Config::get('@app.common.message_ttl_when_complete');
            }
            else
            {
                $ttl = 0;
            }
            MessageLogic::set($data->messageId, $message, $ttl);
            if($ttl > 0)
            {
                MessageExpire::add($message, $ttl);
            }
        }

        if(!Text::isEmpty($message->groupId))
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
     * @return \SixMQ\Struct\Queue\Message|null
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
            if(!Text::isEmpty($message->groupId))
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
            QueueLogic::remove($message->queueId, $message->messageId, true);
            
            MessageCountLogic::removeFailedMessage($message->messageId, $message->queueId);
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
        // 处理push阻塞推送
        imigo(function() use($messageId){
            QueuePushBlockLogic::complete($messageId);
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
        QueueLogic::remove($queueId, $messageId, false);

        // 消息执行超时
        $message = MessageLogic::get($messageId);

        // 移出超时工作集合
        TimeoutLogic::remove($queueId, $messageId);

        $message->consum = true;
        $message->success = false;
        $message->resultData = 'Not being consumed';
        $message->status = MessageStatus::TIMEOUT;

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
    }
}