<?php
namespace SixMQ\Logic;

use Imi\ServerManage;
use Imi\ConnectContext;
use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use SixMQ\Util\HashTable;
use SixMQ\Util\DataParser;
use SixMQ\Util\HashTableNames;
use SixMQ\Service\QueueService;


abstract class QueuePushBlockLogic
{
    /**
     * 增加监听
     *
     * @param int $fd
     * @param \SixMQ\Struct\Queue\Client\Push $data
     * @param \SixMQ\Struct\Queue\Server\Push $return
     * @return void
     */
    public static function add($fd, $data, $return)
    {
        $return->flag = $data->flag;
        $saveData = [
            'fd'            =>    $fd,
            'pushData'      =>    $data,
            'serverPush'    =>    $return,
            'time'          =>    microtime(true),
        ];
        HashTable::set(HashTableNames::QUEUE_PUSH_BLOCK, $return->messageId, json_encode($saveData));
        $saveData['messageId'] = $return->messageId;
        ConnectContext::set('blockStatus', [
            'type'  =>  'push',
            'data'  =>  $saveData,
        ]);
    }

    /**
     * 完成消息处理
     *
     * @param string $messageId
     * @return void
     */
    public static function complete($messageId)
    {
        $data = HashTable::get(HashTableNames::QUEUE_PUSH_BLOCK, $messageId);
        HashTable::del(HashTableNames::QUEUE_PUSH_BLOCK, $messageId);
        if($data)
        {
            $data = json_decode($data, true);
        }
        if(isset($data['fd']) && (-1 === $data['pushData']['block'] || $data['time'] + $data['pushData']['block'] > microtime(true)))
        {
            $message = QueueService::getMessage($messageId);
            $data['serverPush']['consum'] = $message->consum;
            $data['serverPush']['resultSuccess'] = $message->success;
            $data['serverPush']['resultData'] = $message->resultData;
            $server = ServerManage::getServer('MQService');
            $swooleServer = $server->getSwooleServer();
            $sendData = DataParser::encode($data['serverPush']);
            if($swooleServer->send($data['fd'], $sendData))
            {
                ConnectContext::set('blockStatus', null, $data['fd']);
            }
        }
    }
}