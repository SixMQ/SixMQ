<?php
namespace SixMQ\MQService;

abstract class Version
{
    /**
     * 版本号
     * v0.0.1 = 0 * 10000 + 0 * 100 + 1 = 1
     * v2.1.3 === 20103
     * v10.2.3 === 100203
     */
    const VERSION = 1;

    /**
     * 将版本转为数字
     * @param string $version
     * @return int
     */
    public static function strToInt($version)
    {
        list($major, $minor, $sub) = explode('.', $version);
        $integerVersion = $major * 10000 + $minor * 100 + $sub;
        return intval($integerVersion);
    }

    /**
     * 将数字转为版本
     * @param int $versionCode
     * @return string
     */
    public static function intToStr($versionCode)
    {
        if(is_numeric($versionCode) && $versionCode >= 10000)
        {
            $version = [
                (int)($versionCode / 10000),
                (int)($versionCode % 10000 / 100),
                $versionCode % 100,
            ];
            return implode('.', $version);
        }
        else
        {
            return $versionCode;
        }
    }
}