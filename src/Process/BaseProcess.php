<?php
namespace SixMQ\Process;

use Swoole\Coroutine;

abstract class BaseProcess extends \Imi\Process\BaseProcess
{
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
                if($subTime < $minTimespan)
                {
                    Coroutine::sleep($minTimespan - $subTime);
                }
                else
                {
                    Coroutine::sleep(0.001);
                }
            }
        });
    }
}