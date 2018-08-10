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
		return 'quque_' . $queueId;
	}

	/**
	 * 获取工作队列
	 *
	 * @param stirng $queueId
	 * @return stirng
	 */
	public static function getWorkingMessageSet($queueId)
	{
		return 'working_set_' . $queueId;
	}
}