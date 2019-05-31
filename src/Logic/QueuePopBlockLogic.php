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

    /**
     * 完成消息处理
     *
     * @param string $queueId
     * @return void
     */
    public static function complete($queueId)
    {
        Redis::use(function(RedisHandler $redis) use($queueId) {
            $redis->publish('imi:popBlock', json_encode([
                'queueId'   =>  $queueId,
            ]));
        });
    }

    /**
     * 完成消息处理
     *
     * @param string $queueId
     * @return void
     */
    public static function completeQueue($queueId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId){
            $server = ServerManage::getServer('MQService');
            $swooleServer = $server->getSwooleServer();
            do{
                // 等待pop的队列弹出
                $key = RedisKey::getQueuePopList($queueId);
                $rawData = $redis->lpop($key);
                if(!$rawData)
                {
                    break;
                }
                $data = json_decode($rawData, true);
                // 超时判断
                if(-1 !== $data['popData']['block'] && $data['time'] + $data['popData']['block'] <= microtime(true))
                {
                    continue;
                }
                $popData = (object)$data['popData'];
                $popData->block = 0;
                // 弹出消息
                $popResult = QueueService::pop($popData, $redis);
                if(!$popResult || !$popResult->success)
                {
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
                break;
            } while(true);
        });
    }
}