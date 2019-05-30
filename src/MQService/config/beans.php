<?php
return [
    'ConnectContextRedis'    =>    [
        'redisPool'        =>    'redis',
    ],
    'GroupRedis'    =>    [
        'redisPool'    =>    'redis',
        'key'        =>    'sixmq:tcp_group',
        'heartbeatTimespan'    =>    5, // 心跳时间，单位：秒
        'heartbeatTtl'    =>    8, // 心跳数据过期时间，单位：秒
    ],
    'ConnectContextRedis'    =>    [
        'redisPool'    =>    'redis',
        'key'        =>    'sixmq:tcp_connect_context',
        'heartbeatTimespan'    =>    5, // 心跳时间，单位：秒
        'heartbeatTtl'    =>    8, // 心跳数据过期时间，单位：秒
        'dataEncode'    =>  'json_encode',
        'dataDecode'    =>  function($data){
            return json_decode($data, true);
        },
    ],
    'TcpDispatcher'    =>    [
        'middlewares'    =>    [
            \Imi\Server\TcpServer\Middleware\RouteMiddleware::class,
            \Imi\Server\TcpServer\Middleware\ActionMiddleware::class,
        ],
    ],
];