<?php

use Silex\Application;

$app = new Application();

$browser = new Buzz\Browser();
$browser->getClient()->setTimeout(1); // TODO To configuration.

$app['buzz.browser'] = $browser;
$app['imagine']      = new Imagine\Gd\Imagine();

require __DIR__.'/controllers.php';

return $app;
