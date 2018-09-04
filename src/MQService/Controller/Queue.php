<?php
namespace SixMQ\MQService\Controller;

use Imi\Config;
use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use SixMQ\Util\GenerateID;
use SixMQ\Service\QueueService;
use SixMQ\Struct\Queue\Message;
use SixMQ\Util\QueueCollection;
use SixMQ\Struct\BaseServerStruct;
use SixMQ\Struct\Queue\Server\Pop;
use SixMQ\Struct\Queue\Server\Reply;
use Imi\Util\CoroutineChannelManager;
use SixMQ\Struct\Queue\Server\GetMessage;
use Imi\Server\Route\Annotation\Tcp\TcpRoute;
use Imi\Server\Route\Annotation\Tcp\TcpAction;
use Imi\Server\Route\Annotation\Tcp\TcpController;

/**
 * @TcpController
 */
class Queue extends Base
{
	/**
	 * 消息入队列
	 * @TcpAction
	 * @TcpRoute({"action"="queue.push"})
	 *
	 * @param \SixMQ\Struct\Queue\Client\Push $data
	 * @return void
	 */
	public function push($data)
	{
		$reply = QueueService::push($data);
		if(null !== $reply)
		{
			$this->reply($reply);
		}
	}

	/**
	 * 消息出队列
	 * @TcpAction
	 * @TcpRoute({"action"="queue.pop"})
	 *
	 * @param \SixMQ\Struct\Queue\Client\Pop $data
	 * @return void
	 */
	public function pop($data)
	{
		$reply = QueueService::pop($data);
		if(null !== $reply && !$this->reply($reply) && $reply->success)
		{
			// 发送失败，回队列
			QueueService::rollbackPop($reply->queueId, $reply->messageId);
		}
	}

	/**
	 * 消息处理完成
	 * @TcpAction
	 * @TcpRoute({"action"="queue.complete"})
	 *
	 * @param \SixMQ\Struct\Queue\Client\Complete $data
	 * @return void
	 */
	public function complete($data)
	{
		$reply = QueueService::complete($data);
		$this->reply($reply);
	}

	/**
	 * 消息处理完成
	 * @TcpAction
	 * @TcpRoute({"action"="queue.getMessage"})
	 *
	 * @param \SixMQ\Struct\Queue\Client\GetMessage $data
	 * @return void
	 */
	public function getMessage($data)
	{
		$message = QueueService::getMessage($data->messageId);
		$success = false !== $message;
		$reply = new GetMessage($success);
		$reply->messageId = $data->messageId;
		if($success)
		{
			$reply->message = $message;
		}
		$this->reply($reply);
	}
}