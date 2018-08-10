#!/usr/bin/env php
<?php
$vendor = dirname(__DIR__) . '/vendor/';
require_once $vendor . 'autoload.php';

\Imi\App::setDebug(true);

require $vendor . 'yurunsoft/imi/bin/imi.php';
