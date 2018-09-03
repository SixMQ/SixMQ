<?php
namespace SixMQ\Util;

use SixMQ\Util\GenerateID;

/**
 * Redis 存储键获取
 */
abstract class RedisKey
{
	/**
	 * 获取消息ID
	 *
	 * @return string
	 */
	public static function getMessageId()
	{
		return GenerateID::get();
	}

	/**
	 * 获取消息队列
	 *
	 * @param stirng $queueId
	 * @return stirng
	 */
	public static function getMessageQueue($queueId)
	{
		return 'sixmq_quque_' . $queueId;
	}

	/**
	 * 获取工作队列
	 *
	 * @param stirng $queueId
	 * @return stirng
	 */
	public static function getWorkingMessageSet($queueId)
	{
		return 'sixmq_working_set_' . $queueId;
	}

	/**
	 * 获取超时队列
	 *
	 * @param string $queueId
	 * @return string
	 */
	public static function getQueueExpireSet($queueId)
	{
		return 'sixmq_queue_expire_' . $queueId;
	}

	/**
	 * 获取队列列表
	 *
	 * @return string
	 */
	public static function getQueueList()
	{
		return 'sixmq_quque_list';
	}

	/**
	 * 获取消息错误计数
	 *
	 * @return string
	 */
	public static function getMessageErrorCount()
	{
		return 'sixmq_message_error_count';
	}
}