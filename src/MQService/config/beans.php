<?php
return [
	'ConnectContextRedis'	=>	[
		'redisPool'		=>	'redis',
	],
	'GroupRedis'	=>	[
		'redisPool'	=>	'redis',
		'key'		=>	'SIXMQ.TCP.GROUP',
		'heartbeatTimespan'	=>	5, // 心跳时间，单位：秒
		'heartbeatTtl'	=>	8, // 心跳数据过期时间，单位：秒
	],
	'ConnectContextRedis'	=>	[
		'redisPool'	=>	'redis',
		'key'		=>	'SIXMQ.TCP.CONNECT_CONTEXT',
		'heartbeatTimespan'	=>	5, // 心跳时间，单位：秒
		'heartbeatTtl'	=>	8, // 心跳数据过期时间，单位：秒
	],
	'TcpDispatcher'	=>	[
		'middlewares'	=>	[
			\Imi\Server\TcpServer\Middleware\RouteMiddleware::class,
			\Imi\Server\TcpServer\Middleware\ActionMiddleware::class,
		],
	],
];