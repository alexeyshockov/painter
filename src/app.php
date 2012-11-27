<?php

use Silex\Application;

use Painter\Proxy;
use Painter\Painter;

use Imagine\Gd\Imagine;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Buzz\Message\Request as BuzzRequest;
use Buzz\Message\Response as BuzzResponse;
use Buzz\Browser;

$app = new Application();

$browser = new Browser();
$browser->getClient()->setTimeout(1); // TODO To configuration.

$proxy   = new Proxy($browser, 'Painter (github.com/alexeyshockov/painter)');
$painter = new Painter(new Imagine());

$app->error(function (\Exception $exception, $code) use ($proxy, $app) {
    if ($app['debug']) {
        return;
    }

    return $proxy->handleError($exception, $code);
});

// TODO To proxy.
$app->get('{url}', function (Request $request) use ($proxy, $painter, $app) {
    return $proxy->call($request, array($painter, 'process'));
})->assert('url', '.+');

return $app;
