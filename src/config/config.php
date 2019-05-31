<?php
use Imi\Server\Type;

// 注释项代表可省略的，使用默认值
return [
    // 项目根命名空间
    'namespace'    =>    'SixMQ',
    // 扫描目录
    'beanScan'    =>    [
        'SixMQ\Process',
        'SixMQ\Listener',
        'SixMQ\Service',
    ],
    // 主服务器配置
    'mainServer'    =>    [
        'namespace'    =>    'SixMQ\Api',
        'type'        =>    Type::HTTP,
        'host'        =>    '0.0.0.0',
        'port'        =>    8089,
        'configs'    =>    [
            // 开发时可以都设为1
            'worker_num'        => 2,
            'task_worker_num'   => 0,
            // 'worker_num'        => 8, // 设置为CPU的1-4倍最合理
            // 'task_worker_num'   => 8, // 根据实际情况设置
            'max_coroutine'     => 40960, // 同时可创建协程数量
        ],
    ],
    // 子服务器（端口监听）配置
    'subServers'        =>    [
        // 子服务器名
        'MQService'    =>    [
            'namespace'    =>    'SixMQ\MQService',
            'type'        =>    Type::TCP_SERVER,
            'host'        =>    '0.0.0.0', // 实际生产应改为内网ip
            'port'        =>    18086,
            'configs'    =>    [
                // 固定包头
                'open_eof_split'        => false,
                'open_length_check'     => true,
                'package_length_type'   => 'N',
                'package_length_offset' => 0,       //第N个字节是包长度的值
                'package_body_offset'   => 4,       //第几个字节开始计算长度
                'package_max_length'    => 2 * 1024 * 1024,  //协议最大长度，默认2M
            ],
            // 数据处理器
            'dataParser'    =>    \SixMQ\Util\DataParser\Json::class,
        ]
    ],
    // 配置文件
    'configs'    =>    [
        'beans'        =>    __DIR__ . '/beans.php',
        'common'    =>    __DIR__ . '/common.php',
    ],
    'pools'    =>    [
        'redis'    =>    [
            'sync'    =>    [
                'pool'    =>    [
                    'class'        =>    \Imi\Redis\SyncRedisPool::class,
                    'config'    =>    [
                        'maxResources'    =>    100,
                        'minResources'    =>    1,
                    ],
                ],
                'resource'    =>    [
                    'host'        =>    '127.0.0.1',
                    'port'        =>    6379,
                    'serialize'   =>    false,
                    // 密码
                    // 'password'    =>    '',
                    // 第几个库
                    // 'db'        =>    0,
                ]
            ],
            'async'    =>    [
                'pool'    =>    [
                    'class'        =>    \Imi\Redis\CoroutineRedisPool::class,
                    'config'    =>    [
                        'maxResources'    =>    100,
                        'minResources'    =>    1,
                    ],
                ],
                'resource'    =>    [
                    'host'        =>    '127.0.0.1',
                    'port'        =>    6379,
                    'serialize'   =>    false,
                    // 密码
                    // 'password'    =>    '',
                    // 第几个库
                    // 'db'        =>    0,
                ]
            ],
        ],
    ],
    'redis' =>  [
        'defaultPool'               =>  'redis',
        'quickFromRequestContext'   =>  false,
    ],
    'coroutineChannels'        =>    [
        'PopBlockQueue'        =>    [64 * 1024],
    ],
];