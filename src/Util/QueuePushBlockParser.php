<?php
namespace SixMQ\Util;

use Imi\ServerManage;
use SixMQ\Util\HashTableNames;
use SixMQ\Service\QueueService;


abstract class QueuePushBlockParser
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
		HashTable::set(HashTableNames::QUEUE_PUSH_BLOCK, $return->messageId, [
			'fd'			=>	$fd,
			'serverPush'	=>	$return,
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
		if(null !== $data)
		{
			$message = QueueService::getMessage($messageId);
			var_dump($message);
			$data['serverPush']->consum = $message->consum;
			$data['serverPush']->resultSuccess = $message->success;
			$data['serverPush']->resultData = $message->resultData;
			HashTable::del(HashTableNames::QUEUE_PUSH_BLOCK, $messageId);
			$server = ServerManage::getServer('MQService');
			$swooleServer = $server->getSwooleServer();
			$sendData = $server->getBean(\Imi\Server\DataParser\DataParser::class)->encode($data['serverPush']);
			$swooleServer->send($data['fd'], $sendData);
		}
	}
}