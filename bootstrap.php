<?php

use tourze\Base\Config;

if (is_file(__DIR__ . '/vendor/autoload.php'))
{
    require_once __DIR__ . '/vendor/autoload.php';
}

Config::addPath(__DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR);
