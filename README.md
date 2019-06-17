# SixMQ

[![Latest Version](https://img.shields.io/packagist/v/sixmq/sixmq.svg)](https://packagist.org/packages/sixmq/sixmq)
[![Php Version](https://img.shields.io/badge/php-%3E=7.1-brightgreen.svg)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=4.3.0-brightgreen.svg)](https://github.com/swoole/swoole-src)
[![IMI License](https://img.shields.io/github/license/SixMQ/SixMQ.svg)](https://github.com/SixMQ/SixMQ/blob/master/LICENSE)

## 介绍

SixMQ 是一款 PHP 消息队列系统，基于 [imi](https://www.imiphp.com/) 框架开发的，运行在 PHP + Swoole 环境下。

SixMQ 消息存储及队列完全依赖 Redis 实现，代码完全可以由 PHP 开发者阅读和修改。

QQ群：17916227 [![点击加群](https://pub.idqqimg.com/wpa/images/group.png "点击加群")](https://jq.qq.com/?_wv=1027&k=5wXf4Zq)，如有问题会有人解答和修复。

## 特性

* 持久化存储
* 消息确认
* 消息延迟
* 消息分组
* 消息自动清理机制
* 客户端跨语言
* 图形化管理界面

### 持久化存储

依靠 Redis 的持久化存储机制，将消息持久化存储下来。

### 消息确认

消息不是从队列出掉就好了，消费端处理完消息后，需要告知服务端这个消息已经消费完成。超过超时时间后，没有回传确认，该消息会再次进入队列被其它消费端消费。

```php
$queue->complete($messageId, $success, $data);
```

### 消息延迟

有些消息，你希望他在未来某个指定的时间才会被消费，这个功能 SixMQ 轻松帮你实现了。

```php
$delay = 60; // 60 秒后被消费
$queue->pushDelay($data, $delay);
```

### 消息分组

使用 SixMQ 你可以针对消息进行分组，在同一个分组中的消息，SixMQ 会保证他们依次执行。只有在前一个消息被消费完成，后一个消息才会开始被消费。

### 消息自动清理机制

当消息被消费后，如果消息长期储存，势必会占用很多存储空间。要知道，大部分消息被成功消费后，基本便是无用了。

SixMQ 支持两种消息自动清理机制：

* 成功立即清理
* 成功后延迟清理

立即清理很好理解，延迟清理可以将成功消费的消息，延迟保留一定时间，然后才会被释放。

### 客户端跨语言

SixMQ 使用 TCP 协议通讯，所以跨语言跨平台通讯完全不成问题。

目前提供有如下客户端：

[PHP Client](https://github.com/SixMQ/SixMQ-Client)

### 图形化管理界面

SixMQ 提供图形化管理界面：https://github.com/SixMQ/sixmq-web

## 文档及示例

[文档传送门](https://github.com/SixMQ/SixMQ-Client/blob/master/doc/README.md)

[示例传送门](https://github.com/SixMQ/SixMQ-Client/tree/master/examples)

## 运行环境

- Linux 系统 (Swoole 不支持在 Windows 上运行)
- [PHP](https://php.net/) >= 7.1
- [Composer](https://getcomposer.org/)
- [Swoole](https://www.swoole.com/) >= 4.3.0
- Redis 扩展

## 版权信息

SixMQ 遵循 Apache2 开源协议发布，并提供免费使用。
