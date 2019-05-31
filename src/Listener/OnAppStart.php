<?php
namespace SixMQ\Listener;

use Imi\Worker;
use SixMQ\Util\Enum;
use SixMQ\Util\RedisKey;
use SixMQ\Util\HashTable;
use SixMQ\Util\HashTableNames;
use Imi\Bean\Annotation\Listener;
use Imi\Server\Event\Param\AppInitEventParam;
use Imi\Server\Event\Listener\IAppInitEventListener;
use Imi\App;

/**
 * @Listener(eventName="IMI.APP.INIT")
 */
class OnAppStart implements IAppInitEventListener
{
    /**
     * 事件处理方法
     * @param AppInitEventParam $e
     * @return void
     */
    public function handle(AppInitEventParam $e)
    {
        foreach(Enum::getList(HashTableNames::class) as $name)
        {
            HashTable::init($name);
        }
        RedisKey::init();
        App::getBean('ConnectionService')->clearConnections();
    }
}