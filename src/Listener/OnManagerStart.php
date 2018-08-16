<?php
namespace SixMQ\Listener;

use Imi\Task\TaskInfo;
use Imi\Task\TaskParam;
use Imi\Task\TaskManager;
use Imi\Process\ProcessManager;
use Imi\Bean\Annotation\Listener;
use Imi\Task\Interfaces\ITaskHandler;
use Imi\Server\Event\Param\StartEventParam;
use Imi\Server\Event\Listener\IStartEventListener;
use Imi\Server\Event\Param\ManagerStartEventParam;
use Imi\Server\Event\Listener\IManagerStartEventListener;
use Imi\Pool\PoolManager;

/**
 * @Listener(eventName="IMI.MAIN_SERVER.MANAGER.START")
 */
class OnManagerStart implements IManagerStartEventListener
{
	/**
	 * 事件处理方法
	 * @param ManagerStartEventParam $e
	 * @return void
	 */
	public function handle(ManagerStartEventParam $e)
	{
		// 队列监控
		$process = ProcessManager::create('SixMQQueueMonitor');
		$process->start();
		echo 'Process [SixMQQueueMonitor] start', PHP_EOL;
	}
}
