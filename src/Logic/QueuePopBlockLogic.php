<?php
namespace SixMQ\Logic;

use Imi\ServerManage;
use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use SixMQ\Util\DataParser;
use SixMQ\Util\HashTableNames;
use SixMQ\Service\QueueService;
use Imi\ConnectContext;
use Imi\RequestContext;
use Imi\Redis\Redis;
use Imi\Redis\RedisHandler;

abstract class QueuePopBlockLogic
{
    /**
     * 增加监听
     *
     * @param int $fd
     * @param \SixMQ\Struct\Queue\Client\Pop $data
     * @return void
     */
    public static function add($fd, $data)
    {
        PoolManager::use('redis', function($resource, $redis) use($fd, $data){
            $key = RedisKey::getQueuePopList($data->queueId);
            $saveData = [
                'fd'        =>    $fd,
                'popData'   =>    $data,
                'time'      =>    microtime(true),
            ];
            $redis->rpush($key, json_encode($saveData));
            ConnectContext::set('blockStatus', [
                'type'  =>  'pop',
                'data'  =>  $saveData,
            ]);
        });
    }

    public static function parsePopBlockReply()
    {
        $server = ServerManage::getServer('MQService');
        $swooleServer = $server->getSwooleServer();
        foreach(QueueLogic::getList() as $queueId)
        {
            $queuePopListKey = RedisKey::getQueuePopList($queueId);
            Redis::use(function(RedisHandler $redis) use($queueId, $queuePopListKey, $swooleServer) {
                do {
                    $rawData = $redis->lpop($queuePopListKey);
                    if(!$rawData)
                    {
                        return;
                    }
                    $data = json_decode($rawData, true);
                    // 超时判断
                    if(-1 !== $data['popData']['block'] && $data['time'] + $data['popData']['block'] <= microtime(true))
                    {
                        continue;
                    }
                    // fd有效判断
                    if(!$swooleServer->exist($data['fd']))
                    {
                        continue;
                    }
                    $popData = (object)$data['popData'];
                    $popData->block = 0;
                    // 弹出消息
                    $popResult = QueueService::pop($popData, $redis);
                    if(!$popResult || !$popResult->success)
                    {
                        // 回队列重新等待
                        $redis->lPush($queuePopListKey, $rawData);
                        break;
                    }
                    $popResult->flag = $popData->flag;
                    $sendData = DataParser::encode($popResult);
                    if($swooleServer->send($data['fd'], $sendData))
                    {
                        ConnectContext::set('blockStatus', null, $data['fd']);
                    }
                    else
                    {
                        QueueService::rollbackPop($queueId, $popResult->messageId);
                    }
                } while(true);
            });
        }
    }
}