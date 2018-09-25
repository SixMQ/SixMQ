<?php
namespace SixMQ\MQService\Controller;

abstract class Base extends \Imi\Controller\TcpController
{
    /**
     * 回复请求结果
     *
     * @param \SixMQ\Struct\BaseServerStruct $data
     * @return int|boolean
     */
    public function reply(\SixMQ\Struct\Queue\Server\Reply $data)
    {
        $data->flag = $this->data->getFormatData()->flag;
        return $this->server->getSwooleServer()->send($this->data->getFd(), $this->encodeMessage($data));
    }
}