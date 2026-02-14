<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

var_dump($_ENV['APP_ENV'], $_ENV['APP_DEBUG']);
exit;

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
