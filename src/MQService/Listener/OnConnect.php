<?php
namespace SixMQ\MQService\Listener;

use Imi\App;
use Imi\Config;
use Imi\Util\Imi;
use Imi\Util\File;
use Imi\ConnectContext;
use SixMQ\Util\HashTable;
use SixMQ\Util\HashTableNames;
use Imi\Bean\Annotation\ClassEventListener;
use Imi\Server\Event\Param\ConnectEventParam;
use Imi\Server\Event\Listener\IConnectEventListener;

/**
 * @ClassEventListener(className="Imi\Server\TcpServer\Server",eventName="connect",priority=PHP_INT_MAX)
 */
class OnConnect implements IConnectEventListener
{
    /**
     * 事件处理方法
     * @param ConnectEventParam $e
     * @return void
     */
    public function handle(ConnectEventParam $e)
    {
        App::getBean('ConnectionService')->addConnection($e->fd);
    }

}