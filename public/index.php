<?php

use App\Kernel;
use EonX\EasySwoole\Runtime\EasySwooleRuntime;
use OpenSwoole\Constant;

require_once \dirname(__DIR__).'/vendor/autoload_runtime.php';

$sockType = \class_exists(Constant::class)
    ? Constant::SOCK_TCP | Constant::SSL
    : \SWOOLE_SOCK_TCP | \SWOOLE_SSL;

$_SERVER['APP_RUNTIME'] = EasySwooleRuntime::class;
$_SERVER['APP_RUNTIME_OPTIONS'] = [
    'settings' => [
        'ssl_cert_file' => '/var/www/secrets/server.cert',
        'ssl_key_file' => '/var/www/secrets/server.key',
    ],
    'sock_type' => $sockType,
];

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
