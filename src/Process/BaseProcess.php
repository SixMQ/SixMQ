<?php
namespace SixMQ\Process;

use Swoole\Coroutine;
use Imi\RequestContext;
use Imi\ServerManage;

abstract class BaseProcess extends \Imi\Process\BaseProcess
{
    public function __construct($data = [])
    {
        parent::__construct($data);
        RequestContext::set('server', ServerManage::getServer('MQService'));
    }

    /**
     * 启动一个协程执行任务
     *
     * @param callable $callable
     * @param int $minTimespan
     * @return void
     */
    protected function goTask($callable, $minTimespan = 1)
    {
        imigo(function() use($callable, $minTimespan){
            while(true)
            {
                $beginTime = microtime(true);
                
                $callable();

                $subTime = microtime(true) - $beginTime;
                Coroutine::sleep(max($minTimespan - $subTime, 0.001));
            }
        });
    }
}