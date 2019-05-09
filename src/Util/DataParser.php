<?php
namespace SixMQ\Util;

use Imi\App;
use Imi\Config;

class DataParser
{
    /**
     * 编码为存储格式
     * @param mixed $data
     * @return mixed
     */
    public static function encode($data)
    {
        return App::getBean(Config::get('@app.subServers.MQService.dataParser'))->encode($data);
    }

    /**
     * 解码为php变量
     * @param mixed $data
     * @return mixed
     */
    public static function decode($data)
    {
        return App::getBean(Config::get('@app.subServers.MQService.dataParser'))->decode($data);
    }
}