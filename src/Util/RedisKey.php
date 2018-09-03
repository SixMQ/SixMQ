<?php
namespace SixMQ\Util;

use SixMQ\Util\GenerateID;

/**
 * Redis 存储键获取
 */
abstract class RedisKey
{
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

}