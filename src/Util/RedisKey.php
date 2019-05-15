<?php
namespace SixMQ\Util;

use Imi\Pool\PoolManager;
use SixMQ\Util\GenerateID;
use SixMQ\Logic\QueueLogic;

/**
 * Redis 存储键获取
 */
abstract class RedisKey
{
    public static function init()
    {
        PoolManager::use('redis', function($resource, $redis) {
            foreach(QueueLogic::getList() as $queueId)
            {
                $redis->del(static::getQueuePopList($queueId));
            }
        });
    }

    /**
     * 获取每日消息ID统计
     *
     * @return void
     */
    public static function getDailyMessageIdCount()
    {
        return 'sixmq:daily_message_count';
    }

    /**
     * 获取消息ID
     *
     * @return string
     */
    public static function getMessageId($messageId)
    {
        return 'sixmq:message:' . $messageId;
    }

    /**
     * 获取消息队列
     *
     * @param stirng $queueId
     * @return stirng
     */
    public static function getMessageQueue($queueId)
    {
        return 'sixmq:quque:' . $queueId;
    }

    /**
     * 获取队列中储存所有消息ID的列表
     *
     * @param stirng $queueId
     * @return stirng
     */
    public static function getQueueAll($queueId)
    {
        return 'sixmq:queue_all:' . $queueId;
    }

    /**
     * 获取工作队列
     *
     * @param stirng $queueId
     * @return stirng
     */
    public static function getWorkingMessageSet($queueId)
    {
        return 'sixmq:working_set:' . $queueId;
    }

    /**
     * 获取超时队列
     *
     * @param string $queueId
     * @return string
     */
    public static function getQueueExpireSet($queueId)
    {
        return 'sixmq:queue_expire:' . $queueId;
    }

    /**
     * 获取队列列表
     *
     * @return string
     */
    public static function getQueueList()
    {
        return 'sixmq:quque_list';
    }

    /**
     * 获取消息错误计数
     *
     * @return string
     */
    public static function getMessageErrorCount()
    {
        return 'sixmq:message_error_count';
    }

    /**
     * 队列pop阻塞返回队列
     *
     * @param string $queueId
     * @return string
     */
    public static function getQueuePopList($queueId)
    {
        return 'sixmq:queue_pop_list:' . $queueId;
    }

    /**
     * 延迟集合
     *
     * @return string
     */
    public static function getDelaySet()
    {
        return 'sixmq:delay_set';
    }

    /**
     * 获取消息分组列表
     *
     * @return string
     */
    public static function getMessageGroupList($queueId, $groupId)
    {
        return 'sixmq:message_group:' . $queueId . ':' . $groupId;
    }

    /**
     * 获取工作中的消息组
     *
     * @return string
     */
    public static function getWorkingMessageGroupsSet()
    {
        return 'sixmq:working_message_groups';
    }

    /**
     * 获取队列消息数统计
     *
     * @return string
     */
    public static function getQueueMessageCount()
    {
        return 'sixmq:queue_message_count';
    }

    /**
     * 获取失败列表
     *
     * @param string $queueId
     * @return string
     */
    public static function getFailedList($queueId)
    {
        return 'sixmq:failed_message:' . $queueId;
    }

}