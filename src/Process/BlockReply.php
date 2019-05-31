<?php
namespace SixMQ\Process;

use Imi\Redis\Redis;
use Imi\ServerManage;
use Imi\RequestContext;
use Imi\Redis\RedisHandler;
use Imi\Process\Annotation\Process;
use SixMQ\Logic\QueuePopBlockLogic;

/**
 * @Process(name="SixMQ-BlockReply", unique=true)
 */
class BlockReply extends BaseProcess
{
    public function run(\Swoole\Process $process)
    {
        echo 'Process [SixMQ-BlockReply] start', PHP_EOL;
        imigo(function(){
            $this->parsePopBlockReply();
        });
    }

    private function parsePopBlockReply()
    {
        RequestContext::set('server', ServerManage::getServer('MQService'));
        Redis::use(function(RedisHandler $redis) {
            $redis->subscribe(['imi:popBlock'], function($instance, $channelName, $message){
                $message = json_decode($message, true);
                QueuePopBlockLogic::completeQueue($message['queueId']);
            });
        });
    }

}