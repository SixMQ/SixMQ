<?php
namespace SixMQ\Logic;

use SixMQ\Util\RedisKey;
use Imi\Pool\PoolManager;

/**
 * 消息分组集合
 */
abstract class MessageGroupLogic
{
    /**
     * 遍历所有工作中的消息分组的ID
     * 
     * @param callable $callable
     *
     * @return string[]
     */
    public static function eachGroups($callable)
    {
        return PoolManager::use('redis', function($resource, $redis) use($callable) {
            $key = RedisKey::getWorkingMessageGroupsSet();
            $it = null;
            $break = false;
            while(false !== ($list = $redis->hScan($key, $it, '*', 1000)))
            {
                foreach($list as $key => $workingMessageId)
                {
                    list($queueId, $groupId) = explode(':', $key);
                    $callable($redis, $queueId, $groupId, $workingMessageId, $break);
                    if($break)
                    {
                        break;
                    }
                }
                if(0 === $it)
                {
                    break;
                }
            }
        });
    }

    /**
     * 遍历组中消息
     *
     * @param string $queueId
     * @param string $groupId
     * @param int $status 状态
     * @param callable $callable
     * @return void
     */
    public static function eachGroupMessages($queueId, $groupId, $status, $callable)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $groupId, $status, $callable) {
            $key = RedisKey::getMessageGroupList($queueId, $groupId);
            $break = false;
            $index = 0;
            $limit = 1000;
            while($list = $redis->zRangeByScore($key, $status, $status, ['limit'=>[$index, $limit]]))
            {
                foreach($list as $messageId)
                {
                    $callable($redis, $messageId, $break);
                    if($break)
                    {
                        break 2;
                    }
                }
                $index += count($list);
            }
        });
    }

    /**
     * 设置消息状态
     *
     * @param string $queueId
     * @param string $groupId
     * @param string $messageId
     * @param int $status
     * @return void
     */
    public static function setMessageStatus($queueId, $groupId, $messageId, $status)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $groupId, $messageId, $status) {
            $key = RedisKey::getMessageGroupList($queueId, $groupId);
            $redis->zAdd($key, $status, $messageId);
        });
    }

    /**
     * 分组中是否有正在工作的消息
     *
     * @param string $queueId
     * @param string $groupId
     * @return boolean
     */
    public static function hasWorkingMessage($queueId, $groupId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($queueId, $groupId) {
            $key = RedisKey::getWorkingMessageGroupsSet();
            return '' !== $redis->hGet($key, $queueId . ':' . $groupId);
        });
    }

    /**
     * 获取分组中正在工作的消息ID
     *
     * @param string $queueId
     * @param string $groupId
     * @return boolean
     */
    public static function getWorkingMessage($queueId, $groupId)
    {
        return PoolManager::use('redis', function($resource, $redis) use($queueId, $groupId) {
            $key = RedisKey::getWorkingMessageGroupsSet();
            return $redis->hGet($key, $queueId . ':' . $groupId);
        });
    }

    /**
     * 添加组进入工作组
     *
     * @param string $queueId
     * @param string $groupId
     * @return void
     */
    public static function addWorkingGroup($queueId, $groupId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $groupId) {
            $key = RedisKey::getWorkingMessageGroupsSet();
            $redis->hSetNx($key, $queueId . ':' . $groupId, '');
        });
    }

    /**
     * 从工作组中移除某个组
     *
     * @param string $queueId
     * @param string $groupId
     * @return void
     */
    public static function removeWorkingGroup($queueId, $groupId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $groupId) {
            $key = RedisKey::getWorkingMessageGroupsSet();
            $redis->hDel($key, $queueId . ':' . $groupId);
        });
    }

    /**
     * 设置组正在工作的消息ID
     *
     * @param string $queueId
     * @param string $groupId
     * @param string $messageId
     * @return void
     */
    public static function setWorkingGroupMessage($queueId, $groupId, $messageId)
    {
        PoolManager::use('redis', function($resource, $redis) use($queueId, $groupId, $messageId) {
            $key = RedisKey::getWorkingMessageGroupsSet();
            $redis->hSet($key, $queueId . ':' . $groupId, $messageId);
        });
    }
}