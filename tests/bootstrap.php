<?php

// Never load a development or production configuration/route cache in the test suite.
foreach (['APP_CONFIG_CACHE', 'APP_ROUTES_CACHE', 'APP_EVENTS_CACHE'] as $key) {
    $path = sys_get_temp_dir().'/quikmedix-testing-'.getmypid().'-'.$key.'.php';
    putenv($key.'='.$path);
    $_ENV[$key] = $_SERVER[$key] = $path;
}

require __DIR__.'/../vendor/autoload.php';
