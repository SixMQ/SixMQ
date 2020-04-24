#!/usr/bin/env php
<?php
use Imi\App;

$vendor = dirname(__DIR__) . '/vendor/';

$loader = require_once $vendor . 'autoload.php';

App::setLoader($loader);
App::setDebug(true);

require $vendor . 'yurunsoft/imi/bin/imi.php';
