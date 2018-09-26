#!/bin/bash

# 切换正式版、开发版composer.json

__DIR__=$(cd `dirname $0`; pwd)

mv "$__DIR__/composer.json" "$__DIR__/composer.json.bak2"
mv "$__DIR__/composer.json.bak" "$__DIR__/composer.json"
mv "$__DIR__/composer.json.bak2" "$__DIR__/composer.json.bak"
