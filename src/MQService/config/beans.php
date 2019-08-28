<?php
return [
    'GroupRedis'    =>    [
        'redisPool'    =>    'redis',
        'key'        =>    'sixmq:tcp_group',
        'heartbeatTimespan'    =>    5, // 心跳时间，单位：秒
        'heartbeatTtl'    =>    8, // 心跳数据过期时间，单位：秒
    ],
    'TcpDispatcher'    =>    [
        'middlewares'    =>    [
            \Imi\Server\TcpServer\Middleware\RouteMiddleware::class,
        ],
    ],
];