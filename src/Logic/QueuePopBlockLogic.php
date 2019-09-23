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
        $saveDatas = [];
        foreach(is_array($data->queueId) ? $data->queueId : [$data->queueId] as $queueId)
        {
            $popData = clone $data;
            $popData->queueId = $queueId;
            $saveDatas[] = [
                'fd'        =>    $fd,
                'popData'   =>    $popData,
                'time'      =>    microtime(true),
            ];
        }
        PoolManager::use('redis', function($resource, RedisHandler $redis) use($fd, $data, $saveDatas){
            $redis->multi();
            foreach($saveDatas as $saveData)
            {
                $key = RedisKey::getQueuePopList($saveData['popData']->queueId);
                $redis->rpush($key, json_encode($saveData));
            }
            $redis->exec();
        });
        ConnectContext::set('blockStatus', [
            'type'  =>  'pop',
            'data'  =>  $saveDatas,
        ], $fd);
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
                        ConnectContext::use(function($data) use($redis){
                            static::removePopItem($data['blockStatus']['data']);
                            unset($data['blockStatus']);
                            return $data;
                        }, $data['fd']);
                    }
                    else
                    {
                        QueueService::rollbackPop($queueId, $popResult->messageId);
                    }
                } while(true);
            });
        }
    }

    /**
     * 移除pop项监听
     *
     * @param array $items
     * @return void
     */
    public static function removePopItem($items)
    {
        foreach($items as $item)
        {
            Redis::lrem(RedisKey::getQueuePopList($item['popData']['queueId']), json_encode($item), 0);
        }
    }
}