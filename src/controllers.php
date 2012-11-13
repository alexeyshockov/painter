<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // TODO Log.

    // TODO Process 415 (type exception)...

    return new Response($e->getMessage(), 500);
});

// TODO Memory limit check?..
$app->get('{url}', function(Request $request) use ($app) {
    $scheme = $request->headers->get('X-Painter-Scheme', 'http');
    $host   = $request->headers->get('X-Painter-Host', '127.0.0.1');
    $uri    = $request->getRequestUri();

    $proxyHeaders = [];
    foreach ($request->headers->all() as $name => $value) {
        if (in_array($name, [
            'host',
            'content-type',
            'content-length',
            'connection',
            'php-auth-user',
            'php-auth-pw',
            'accept'
        ])) {
            continue;
        }

        if (preg_match('/^x-painter/i', $name)) {
            continue;
        }

        // TODO Process more then one value...
        $proxyHeaders[$name] = $value[0];
    }

    /** @var \Buzz\Message\Response */
    $proxyResponse = $app['buzz.browser']->get($scheme.'://'.$host.$uri, $proxyHeaders);

    if ($proxyResponse->getStatusCode() != 200) {
        // TODO Check content type...
        $image = $proxyResponse->getContent();

        // And transform...
        $box    = $request->headers->get('X-Painter-Box', '100x100');
        // TODO crop_face filter.
        $filter = $request->headers->get('X-Painter-Filter', 'thumbnail');

        list($width, $height) = explode('x', $box);

        // TODO Check Accept header (*/* or JPEG).

        $size = new Imagine\Image\Box($width, $height);

        $processedImage = $app['imagine']->load($image)->thumbnail($size);

        return new StreamedResponse(
            function() use($processedImage) {
                $processedImage->show('jpeg');
            }
        );
    } else {
        // TODO Log (access log).

        // TODO Headers.
        return new Response($proxyResponse->getContent(), $proxyResponse->getStatusCode());
    }
})->assert('url', '.+');
