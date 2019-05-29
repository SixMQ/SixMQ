<?php
return [
    // 配置文件
    'configs'    =>    [
        'beans'        =>    __DIR__ . '/beans.php',
    ],
    'beanScan'    =>    [
        'SixMQ\MQService\Controller',
        'SixMQ\WorkerListener',
        'SixMQ\MQService\Listener',
        'SixMQ\MQService\Service',
    ],
];