<?php
return [
    // 序列化格式
    'serialization'                 =>    \Imi\Util\Format\Json::class,
    // redis 自增键名，代入date()中，所以Y、m、d这些是可用的。这个0001一般用于分布式。
    'redis_id_key'                  =>    '0001-{Y}{m}{d}',
    'id_format'                     =>    '0001-{Y}{m}{d}-{id}',
    'queue_block_time'              =>    600,
    // 当完成消息时，立即丢弃消息。不能使用push阻塞获取结果。
    'drop_message_when_complete'    =>  false,
    // 当消息完成时，对消息设置ttl，为null则不超时。drop_message_when_complete 为 true 时不生效。
    'message_ttl_when_complete'     =>  900,
];