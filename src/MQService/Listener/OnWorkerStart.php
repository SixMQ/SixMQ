<?php
namespace SixMQ\MQService\Listener;

use Imi\Config;
use Imi\Util\Imi;
use Imi\Util\File;
use Imi\Event\Event;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;
use Imi\Bean\Annotation\Listener;

/**
 * @Listener("IMI.INIT.WORKER.BEFORE")
 */
class OnWorkerStart implements IEventListener
{
    /**
     * 事件处理方法
     * @param EventParam $e
     * @return void
     */
    public function handle(EventParam $e)
    {
        $this->update();
        // 暂定 1 分钟更新一次配置
        $timerId = \Swoole\Timer::tick(60 * 1000, imiCallable([$this, 'update']));
        Event::on('IMI.MAIN_SERVER.WORKER.EXIT', function() use($timerId){
            \Swoole\Timer::clear($timerId);
        });
    }

    public function update()
    {
        $file = File::path(Imi::getNamespacePath('SixMQ\config'), 'auth.json');
        $authConfig = json_decode(file_get_contents($file), true);
        Config::setConfig('auth', $authConfig);
    }
}