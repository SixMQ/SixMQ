<?php
namespace SixMQ\Util\DataParser;

use SixMQ\MQService\Version;
use Imi\Server\DataParser\IParser;

class Json implements IParser
{
    /**
     * 编码为存储格式
     * @param mixed $data
     * @return mixed
     */
    public function encode($data)
    {
        $sendData = json_encode($data);
        return pack('NNa*', Version::VERSION, strlen($sendData), $sendData);
    }
    
    /**
     * 解码为php变量
     * @param mixed $data
     * @return mixed
     */
    public function decode($data)
    {
        return \json_decode(substr($data, 4));
    }
}