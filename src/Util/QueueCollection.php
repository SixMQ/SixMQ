<?php
namespace SixMQ\Util;

use Imi\Pool\PoolManager;

abstract class QueueCollection
{
	/**
	 * 获取所有队列的ID
	 *
	 * @return string[]
	 */
	public static function getList()
	{
		return PoolManager::use('redis', function($resource, $redis) {
			return $redis->smembers(RedisKey::getQueueList());
		});
	}

	/**
	 * 队列是否存在
	 *
	 * @param string $queueId
	 * @return boolean
	 */
	public static function has($queueId)
	{
		return PoolManager::use('redis', function($resource, $redis) use($queueId) {
			return $redis->sIsMember(RedisKey::getQueueList(), $queueId);
		});
	}

	/**
	 * 增加队列
	 *
	 * @param string $queueId
	 * @return boolean
	 */
	public static function append($queueId)
	{
		return PoolManager::use('redis', function($resource, $redis) use($queueId) {
			return $redis->sadd(RedisKey::getQueueList(), $queueId);
		}) > 0;
	}
}