<?php
namespace SixMQ\MQService\Listener;

use Imi\App;
use Imi\Config;
use Imi\Util\Imi;
use Imi\Util\File;
use Imi\Bean\Annotation\ClassEventListener;
use Imi\Server\Event\Param\CloseEventParam;
use Imi\Server\Event\Listener\ICloseEventListener;

/**
 * @ClassEventListener(className="Imi\Server\TcpServer\Server",eventName="close",priority=PHP_INT_MAX)
 */
class OnClose implements ICloseEventListener
{
    /**
     * 事件处理方法
     * @param CloseEventParam $e
     * @return void
     */
    public function handle(CloseEventParam $e)
    {
        // 移除连接
        App::getBean('ConnectionService')->removeConnection($e->fd);
    }

}