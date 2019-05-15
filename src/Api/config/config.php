<?php
return [
    'beanScan'  =>  [
        'SixMQ\Api\Controller',
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