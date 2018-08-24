# SixMQ

[![Latest Version](https://img.shields.io/packagist/v/sixmq/sixmq.svg)](https://packagist.org/packages/sixmq/sixmq)
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=4.0.0-brightgreen.svg)](https://github.com/swoole/swoole-src)
[![Hiredis Version](https://img.shields.io/badge/hiredis-%3E=0.1-brightgreen.svg)](https://github.com/redis/hiredis)
[![IMI License](https://img.shields.io/github/license/SixMQ/SixMQ.svg)](https://github.com/SixMQ/SixMQ/blob/master/LICENSE)

## 介绍

SixMQ 是一款 PHP 消息队列系统，基于 [imi](https://www.imiphp.com/) 框架开发的，运行在 PHP + Swoole 环境下。

SixMQ 消息存储及队列完全依赖 Redis 实现，支持消息处理超时处理、协程挂起等待数据返回等特性。

我们希望把 SixMQ 打造成 PHP 界小型轻量级消息队列系统。

## 文档

正在编写中……

QQ群：17916227 [![点击加群](https://pub.idqqimg.com/wpa/images/group.png "点击加群")](https://jq.qq.com/?_wv=1027&k=5wXf4Zq)，如有问题会有人解答和修复。

## 运行环境

- [PHP](https://php.net/) >= 7.0
- [Composer](https://getcomposer.org/)
- [Swoole](https://www.swoole.com/) >= 4.0.0 (必须启用协程，如使用 Redis 请开启)
- [Hiredis](https://github.com/redis/hiredis/releases) (需要在安装 Swoole 之前装)

## 版权信息

SixMQ 遵循 Apache2 开源协议发布，并提供免费使用。
