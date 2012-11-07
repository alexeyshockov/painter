<?php

use Silex\Application;

$app = new Application();

$app['buzz.browser'] = new Buzz\Browser();
$app['imagine']      = new Imagine\Gd\Imagine();

require __DIR__.'/controllers.php';

return $app;
