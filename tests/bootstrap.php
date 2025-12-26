<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    $dotenv = new Dotenv();
    $dotenv->loadEnv(dirname(__DIR__) . '/.env');
    $dotenv->overload(dirname(__DIR__) . '/.env.test');

    foreach ($_SERVER as $key => $val) {
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $val;
        }
    }
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
