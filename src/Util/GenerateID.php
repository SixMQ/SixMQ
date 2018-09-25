<?php
namespace SixMQ\Util;

use Imi\Config;
use Imi\Pool\PoolManager;


abstract class GenerateID
{
    /**
     * date()函数支持的格式
     */
    const DATE_FORMATS = [
        'd',
        'D',
        'j',
        'l',
        'N',
        'S',
        'w',
        'z',
        'W',
        'F',
        'm',
        'M',
        'n',
        't',
        'L',
        'o',
        'Y',
        'y',
        'a',
        'A',
        'B',
        'g',
        'G',
        'h',
        'H',
        'i',
        's',
        'u',
        'e',
        'I',
        'O',
        'P',
        'T',
        'Z',
        'c',
        'r',
        'U',
    ];

    public static function get()
    {
        $time = time();
        $id = PoolManager::use('redis', function($resource, $redis) use($time){
            $key = static::parseRule(Config::get('@app.common.redis_id_key'), $time);
            return $redis->hIncrBy(RedisKey::getDailyMessageIdCount(), $key, 1);
        });
        return static::parseRule(Config::get('@app.common.id_format'), $time, [
            'id'    =>    $id,
        ]);
    }

    /**
     * 获取替换参数
     * @param int $timestamp
     * @param array $data
     * @return void
     */
    protected static function getReplace($timestamp, $data = [])
    {
        static $cache = [];

        if(isset($cache[$timestamp]))
        {
            $result = $cache[$timestamp];
        }
        else
        {
            $result = [[], []];
            foreach(static::DATE_FORMATS as $format)
            {
                $result[0][] = '{' . $format . '}';
                $result[1][] = date($format, $timestamp);
            }
            $cache = [$timestamp => $result];
        }
        
        foreach($data as $k => $v)
        {
            $result[0][] = '{' . $k . '}';
            $result[1][] = $v;
        }

        return $result;
    }

    protected static function parseRule($rule, $timestamp, $data = [])
    {
        list($search, $replace) = static::getReplace($timestamp, $data);
        return str_replace($search, $replace, $rule);
    }
}