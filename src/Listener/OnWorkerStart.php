<?php
namespace SixMQ\Listener;

use Imi\Worker;
use SixMQ\Util\HashTable;
use Imi\Bean\Annotation\Listener;
use Imi\Server\Event\Param\WorkerStartEventParam;
use Imi\Server\Event\Listener\IWorkerStartEventListener;
use SixMQ\Util\Enum;
use SixMQ\Util\HashTableNames;

/**
 * @Listener(eventName="IMI.MAIN_SERVER.WORKER.START")
 */
class OnWorkerStart implements IWorkerStartEventListener
{
	/**
	 * 事件处理方法
	 * @param WorkerStartEventParam $e
	 * @return void
	 */
	public function handle(WorkerStartEventParam $e)
	{
		if(0 === Worker::getWorkerID())
		{
			foreach(Enum::getList(HashTableNames::class) as $name)
			{
				HashTable::init($name);
			}
		}
	}
}