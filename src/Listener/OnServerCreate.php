<?php
namespace SixMQ\Listener;

use Imi\Task\TaskInfo;
use Imi\Task\TaskParam;
use Imi\Event\EventParam;
use Imi\Pool\PoolManager;
use Imi\Task\TaskManager;
use Imi\Event\IEventListener;
use Imi\Process\ProcessManager;
use Imi\Bean\Annotation\Listener;
use Imi\Task\Interfaces\ITaskHandler;
use Imi\Server\Event\Param\StartEventParam;
use Imi\Server\Event\Listener\IStartEventListener;

/**
 * @Listener(eventName="IMI.SERVERS.CREATE.AFTER")
 */
class OnServerCreate implements IEventListener
{
    /**
     * 事件处理方法
     * @param EventParam $e
     * @return void
     */
    public function handle(EventParam $e)
    {
        // 队列监控
        ProcessManager::runWithManager('SixMQQueueMonitor');
    }
}
