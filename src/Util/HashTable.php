<?php
namespace SixMQ\Util;

use Imi\Pool\PoolManager;

abstract class HashTable
{
	/**
	 * 初始化
	 *
	 * @param string $hashTableName
	 * @return void
	 */
	public static function init($hashTableName)
	{
		static::clear($hashTableName);
	}

	/**
	 * 设置值
	 *
	 * @param string $hashTableName
	 * @param string $fieldName
	 * @param mixed $value
	 * @return boolean
	 */
	public static function set($hashTableName, $fieldName, $value)
	{
		return PoolManager::use('redis', function($resource, $redis) use($hashTableName, $fieldName, $value){
			return $redis->hset($hashTableName, $fieldName, $value);
		});
	}

	/**
	 * 获取值
	 *
	 * @param string $hashTableName
	 * @param string $fieldName
	 * @return mixed|boolean
	 */
	public static function get($hashTableName, $fieldName)
	{
		return PoolManager::use('redis', function($resource, $redis) use($hashTableName, $fieldName){
			return $redis->hget($hashTableName, $fieldName);
		});
	}

	/**
	 * 删除
	 *
	 * @param string $hashTableName
	 * @param string $fieldName
	 * @return boolean
	 */
	public static function del($hashTableName, $fieldName)
	{
		return PoolManager::use('redis', function($resource, $redis) use($hashTableName, $fieldName){
			return $redis->hdel($hashTableName, $fieldName);
		}) > 0;
	}

	/**
	 * 键值对是否存在
	 *
	 * @param string $hashTableName
	 * @param string $fieldName
	 * @return boolean
	 */
	public static function exists($hashTableName, $fieldName)
	{
		return PoolManager::use('redis', function($resource, $redis) use($hashTableName, $fieldName){
			return $redis->hexists($hashTableName, $fieldName);
		});
	}

	/**
	 * 清空
	 *
	 * @param string $hashTableName
	 * @return boolean
	 */
	public static function clear($hashTableName)
	{
		return PoolManager::use('redis', function($resource, $redis) use($hashTableName){
			return $redis->del($hashTableName);
		}) > 0;
	}

	/**
	 * 统计键数量
	 *
	 * @param string $hashTableName
	 * @return int
	 */
	public static function count($hashTableName)
	{
		return PoolManager::use('redis', function($resource, $redis) use($hashTableName){
			return $redis->hlen($hashTableName);
		});
	}

	/**
	 * 返回所有键
	 *
	 * @param string $hashTableName
	 * @return string[]
	 */
	public static function keys($hashTableName)
	{
		return PoolManager::use('redis', function($resource, $redis) use($hashTableName){
			return $redis->hkeys($hashTableName);
		});
	}
}