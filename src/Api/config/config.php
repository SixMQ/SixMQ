<?php
return [
    'beanScan'  =>  [
        'SixMQ\Api\Aop',
        'SixMQ\Api\Controller',
        'SixMQ\Api\Service',
        'SixMQ\Api\Enums',
        'SixMQ\Api\Middleware',
        'SixMQ\WorkerListener',
    ],
    'beans'	=>	[
        'SessionManager'    =>    [
            // 指定Session存储驱动类
            'handlerClass'    =>    \Imi\Server\Session\Handler\File::class,
        ],
        'SessionFile'    =>    [
            'savePath'    =>    dirname(__DIR__, 2) . '/.session/',
        ],
        'SessionConfig'    =>    [
            // session 名称，默认为imisid
            // 'name'    =>    '',
            // 每次请求完成后触发垃圾回收的概率，默认为1%，可取值0~1.0，概率为0%~100%
            // 'gcProbability'    =>    0.1,
            // 最大存活时间，默认30天，单位秒
            // 'maxLifeTime'=>    0.1,
            // session 前缀
            // 'prefix' => 'imi',
        ],
        'SessionCookie'    =>    [
            // Cookie 的 生命周期，以秒为单位。
            'lifetime'    =>    86400 * 30,
            // 此 cookie 的有效 路径。 on the domain where 设置为“/”表示对于本域上所有的路径此 cookie 都可用。
            // 'path'        =>    '',
            // Cookie 的作用 域。 例如：“www.php.net”。 如果要让 cookie 在所有的子域中都可用，此参数必须以点（.）开头，例如：“.php.net”。
            // 'domain'    =>    '',
            // 设置为 TRUE 表示 cookie 仅在使用 安全 链接时可用。
            // 'secure'    =>    false,
            // 设置为 TRUE 表示 PHP 发送 cookie 的时候会使用 httponly 标记。
            // 'httponly'    =>    false,
        ],
		'HttpDispatcher'	=>	[
			'middlewares'	=>	[
                \SixMQ\Api\Middleware\CrossDomain::class,
				\Imi\Server\Session\Middleware\HttpSessionMiddleware::class,
				\Imi\Server\Http\Middleware\RouteMiddleware::class,
			],
		],
        'HttpNotFoundHandler'    =>    [
            'handler'    =>    \SixMQ\Api\ErrorHandler\HttpNotFoundHandler::class,
        ],
        'HttpErrorHandler'    =>    [
            'handler'    =>    \SixMQ\Api\ErrorHandler\HttpErrorHandler::class,
        ],
	],
];