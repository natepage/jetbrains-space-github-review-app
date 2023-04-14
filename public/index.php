<?php

use App\Kernel;
use EonX\EasySwoole\Runtime\EasySwooleRuntime;

require_once \dirname(__DIR__).'/vendor/autoload_runtime.php';

$_SERVER['APP_RUNTIME'] = EasySwooleRuntime::class;

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
