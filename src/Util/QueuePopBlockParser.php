<?php
namespace SixMQ\Util;

use Imi\ServerManage;
use Imi\Pool\PoolManager;
use SixMQ\Util\HashTableNames;
use SixMQ\Service\QueueService;


abstract class QueuePopBlockParser
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
			$redis->rpush($key, [
				'fd'			=>	$fd,
				'popData'		=>	$data,
				'time'			=>	microtime(true),
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
		PoolManager::use('redis', function($resource, $redis) use($queueId){
			$server = ServerManage::getServer('MQService');
			$swooleServer = $server->getSwooleServer();
			do{
				// 等待pop的队列弹出
				$key = RedisKey::getQueuePopList($queueId);
				$data = $redis->lpop($key);
				if(!$data)
				{
					break;
				}
				// 超时判断
				if(-1 !== $data['popData']->block && $data['time'] + $data['popData']->block <= microtime(true))
				{
					continue;
				}
				$popData = clone $data['popData'];
				$popData->block = 0;
				// 弹出消息
				$popResult = QueueService::pop($popData, $redis);
				if(!$popResult->success)
				{
					break;
				}
				$popResult->flag = $popData->flag;
				$sendData = $server->getBean(\Imi\Server\DataParser\DataParser::class)->encode($popResult);
				if(!$swooleServer->send($data['fd'], $sendData))
				{
					QueueService::rollbackPop($queueId, $popResult->messageId);
				}
				break;
			} while(true);
		});
	}
}