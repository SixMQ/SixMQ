<?php
return [
    'beanScan'  =>  [
        'SixMQ\Api\Controller',
        'SixMQ\Api\Service',
        'SixMQ\Api\Enums',
    ],
    'beans'	=>	[
		'HttpDispatcher'	=>	[
			'middlewares'	=>	[
				// \Imi\Server\Session\Middleware\HttpSessionMiddleware::class,
				\Imi\Server\Http\Middleware\RouteMiddleware::class,
			],
		],
	],
];