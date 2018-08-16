<?php
namespace SixMQ\InitListener;

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

		// $taskHandler = new class implements ITaskHandler{
		// 	/**
		// 	 * 任务处理方法
		// 	 * @param TaskParam $param
		// 	 * @param \Swoole\Server $server
		// 	 * @param integer $taskID
		// 	 * @param integer $WorkerID
		// 	 * @return void
		// 	 */
		// 	public function handle(TaskParam $param, \Swoole\Server $server, int $taskID, int $WorkerID)
		// 	{
		// 		var_dump('task');
		// 	}

		// 	/**
		// 	 * 任务结束时触发
		// 	 * @param \swoole_server $server
		// 	 * @param int $taskId
		// 	 * @param mixed $data
		// 	 * @return void
		// 	 */
		// 	public function finish(\Swoole\Server $server, int $taskID, $data)
		// 	{
		// 		var_dump('finish');
		// 	}
		// };
		// $taskParam = new TaskParam();
		// $taskInfo = new TaskInfo($taskHandler, $taskParam);
		// var_dump(TaskManager::post($taskInfo));

		// var_dump('start', $e->server->getSwooleServer());

		// var_dump('ms');
	}
}
