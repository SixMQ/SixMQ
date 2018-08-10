<?php
return [
	// 序列化格式
	'serialization'		=>	\Imi\Util\Format\Json::class,
	// redis 自增键名，代入date()中，所以Y、m、d这些是可用的。这个0001一般用于分布式。
	'redis_id_key'		=>	'0001-{Y}{m}{d}',
	'id_format'			=>	'0001-{Y}{m}{d}-{id}',
];