<?php
namespace SixMQ\Service;

use Imi\Redis\Redis;
use Imi\Util\Pagination;
use Imi\Redis\RedisHandler;
use Imi\Bean\Annotation\Bean;
use Imi\RequestContext;
use Imi\ConnectContext;

/**
 * @Bean("ConnectionService")
 */
class ConnectionService
{
    /**
     * 连接上下文存储key
     */
    const STORE_KEY = 'sixmq:tcp_connect_context:store';

    /**
     * 连接列表key
     */
    const CONNECTION_LIST_KEY = 'sixmq:connections';

    /**
     * 增加连接
     *
     * @param int $fd
     * @return void
     */
    public function addConnection($fd)
    {
        Redis::use(function(RedisHandler $redis) use($fd) {
            return $redis->rPush(self::CONNECTION_LIST_KEY, $fd);
        });
    }

    /**
     * 移除连接
     *
     * @param int $fd
     * @return void
     */
    public function removeConnection($fd)
    {
        Redis::use(function(RedisHandler $redis) use($fd) {
            return $redis->lrem(self::CONNECTION_LIST_KEY, $fd, 1);
        });
    }

    /**
     * 清除连接
     *
     * @return void
     */
    public function clearConnections()
    {
        Redis::use(function(RedisHandler $redis) {
            return $redis->del(self::CONNECTION_LIST_KEY);
        });
    }

    /**
     * 查询列表
     *
     * @param int $page
     * @param int $count
     * @param integer $pages
     * @return array
     */
    public function selectList($page, $count, &$pages = 0)
    {
        $pagination = new Pagination($page, $count);

        $fds = Redis::use(function(RedisHandler $redis) use($pagination) {
            return $redis->lrange(self::CONNECTION_LIST_KEY, $pagination->getLimitOffset(), $pagination->getLimitEndOffset());
        });;

        $records = $this->getCount();
        $pages = $pagination->calcPageCount($records);

        $server = RequestContext::getServer()->getSwooleServer();
        $list = [];
        foreach($fds as $fd)
        {
            $list[] = [
                'fd'            =>  $fd,
                'clientInfo'    =>  $server->getClientInfo($fd),
                'blockStatus'   =>  ConnectContext::get('blockStatus', null, $fd),
            ];
        }

        return $list;
    }

    /**
     * 获取连接总数
     *
     * @return int
     */
    public function getCount()
    {
        return (int)Redis::use(function(RedisHandler $redis){
            return $redis->lLen(self::CONNECTION_LIST_KEY);
        });
    }

}