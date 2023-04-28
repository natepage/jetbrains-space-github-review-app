<?php

use App\Kernel;
use EonX\EasyBugsnag\Interfaces\ValueOptionInterface;

require_once \dirname(__DIR__).'/vendor/autoload_runtime.php';

$_SERVER[ValueOptionInterface::RESOLVE_REQUEST_IN_CLI] = true;

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
