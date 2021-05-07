<?php

require __DIR__.'/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('MYSQL_HOST', '10.211.55.6');
define('MYSQL_USER', 'jobd');
define('MYSQL_PASSWORD', 'password');
define('MYSQL_DB', 'jobd');

define('JOBD_TABLE', 'jobs2');
define('JOBD_HOST', '127.0.0.1');
define('JOBD_PORT', jobd\Client::MASTER_PORT);
define('JOBD_PASSWORD', '');

spl_autoload_register(function($class) {
    if (strpos($class, '\\') !== false) {
        $class = str_replace('\\', '/', $class);
        $root = __DIR__;
    } else {
        $root = __DIR__.'/classes';
    }

    $path = $root.'/'.$class.'.php';

    if (is_file($path))
        require_once $path;
});

include __DIR__.'/functions.php';