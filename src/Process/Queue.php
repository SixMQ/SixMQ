<?php
namespace SixMQ\Process;

use Swoole\Coroutine;
use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;
use SixMQ\Service\QueueService;
use SixMQ\Logic\QueueLogic;
use Imi\Process\Annotation\Process;
use SixMQ\Logic\MessageGroupLogic;
use SixMQ\Struct\Queue\GroupMessageStatus;
use SixMQ\Logic\MessageWorkingLogic;
use SixMQ\Logic\DelayLogic;
use SixMQ\Logic\TimeoutLogic;
use SixMQ\Logic\MessageLogic;
use Imi\RequestContext;
use Imi\ServerManage;

/**
 * @Process(name="SixMQ-QueueMonitor", unique=true)
 */
class Queue extends BaseProcess
{
    public function run(\Swoole\Process $process)
    {
        echo 'Process [SixMQ-QueueMonitor] start', PHP_EOL;
        // 消息超时
        $this->goTask(function(){
            foreach(QueueLogic::getList() as $queueId)
            {
                $this->checkMessageExpire($queueId);
            }
        });
        // 任务超时
        $this->goTask(function(){
            foreach(QueueLogic::getList() as $queueId)
            {
                $this->checkTaskExpire($queueId);
            }
        });
        // 消息延迟
        $this->goTask(function(){
            $this->parseDelayMessage();
        });
        // 消息分组
        $this->goTask(function(){
            $this->parseMessageGroup();
        });
    }

    /**
     * 检查消息超时
     *
     * @param string $queueId
     * @return void
     */
    private function checkMessageExpire($queueId)
    {
        do{
            $list = TimeoutLogic::getList($queueId, microtime(true), 0, 1000);
            foreach($list as $messageId)
            {
                QueueService::expireMessage($queueId, $messageId);
            }
        }while([] !== $list);
    }

    /**
     * 检查任务超时
     *
     * @param string $queueId
     * @return void
     */
    private function checkTaskExpire($queueId)
    {
        do{
            $list = MessageWorkingLogic::getList($queueId, microtime(true), 0, 1000);
            foreach($list as $messageId)
            {
                QueueService::expireTask($queueId, $messageId);
            }
        }while([] !== $list);
    }

    /**
     * 消息延迟处理
     *
     * @return void
     */
    private function parseDelayMessage()
    {
        do{
            $list = DelayLogic::getList(microtime(true), 0, 1000);
            foreach($list as $messageId)
            {
                QueueService::delayToQueue($messageId);
            }
        }while([] !== $list);
    }

    /**
     * 处理消息分组
     *
     * @return void
     */
    private function parseMessageGroup()
    {
        RequestContext::set('server', ServerManage::getServer('MQService'));
        MessageGroupLogic::eachGroups(function($redis, $queueId, $groupId, $workingMessageId, &$break){
            if('' !== $workingMessageId)
            {
                return;
            }
            $hasMessage = false;
            MessageGroupLogic::eachGroupMessages($queueId, $groupId, GroupMessageStatus::FREE, function($redis, $messageId, &$break) use($queueId, $groupId, &$hasMessage){
                $hasMessage = true;
                $message = QueueService::getMessage($messageId);
                $isDelay = $message->delay > 0;
                MessageGroupLogic::setMessageStatus($queueId, $groupId, $message->messageId, GroupMessageStatus::WORKING);
                MessageGroupLogic::setWorkingGroupMessage($message->queueId, $message->groupId, $message->messageId);
                if($isDelay)
                {
                    $message->delayRunTime = $message->inTime + $message->delay;
                    // 消息存储
                    MessageLogic::set($messageId, $message);
                    // 加入延迟集合
                    DelayLogic::add($messageId, $message->delayRunTime);
                }
                else
                {
                    // 加入消息队列
                    QueueLogic::rpush($queueId, $messageId);
                    // 加入超时队列
                    if($message->timeout > -1)
                    {
                        TimeoutLogic::push($queueId, $messageId, microtime(true) + $message->timeout);
                    }
                }
                $break = true;
            });
            if(!$hasMessage)
            {
                MessageGroupLogic::removeWorkingGroup($queueId, $groupId);
            }
        });
    }
}