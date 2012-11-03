<?php

if (extension_loaded('newrelic')) {
    newrelic_set_appname(getenv('NEWRELIC_APP_NAME') ?: 'Painter');
    newrelic_name_transaction($_SERVER['REQUEST_URI']);
}

require_once __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../src/app.php';

$app->run();
