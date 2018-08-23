<?php
namespace SixMQ\Util;

abstract class Enum
{
	/**
	 * 获取枚举列表
	 *
	 * @param string $className
	 * @return array|null
	 */
	public static function getList($className)
	{
		try{
			$ref = new \ReflectionClass($className);
			return $ref->getConstants();
		}
		catch(\Throwable $e)
		{
			return null;
		}
	}
}