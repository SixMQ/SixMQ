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
use Imi\Server\Event\Param\CloseEventParam;
use Imi\Server\Event\Listener\ICloseEventListener;
use SixMQ\Logic\QueuePopBlockLogic;

/**
 * @ClassEventListener(className="Imi\Server\TcpServer\Server",eventName="close",priority=19940311)
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
        // 移除push相关数据
        $blockStatus = ConnectContext::get('blockStatus');
        if(isset($blockStatus['type']))
        {
            if('push' === $blockStatus['type'])
            {
                HashTable::del(HashTableNames::QUEUE_PUSH_BLOCK, $blockStatus['data']['messageId']);
            }
            else
            {
                QueuePopBlockLogic::removePopItem($blockStatus['data']);
            }
        }
        // 移除连接
        App::getBean('ConnectionService')->removeConnection($e->fd);
    }

}