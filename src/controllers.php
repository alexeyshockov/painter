<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

// TODO Memory limit check?..
$app->get('.+', function(Request $request) use ($app) {
    $scheme = $request->headers->get('X-Painter-Scheme', 'http');
    $host   = $request->headers->get('X-Painter-Host', '127.0.0.1');
    $uri    = $request->getRequestUri();

    $proxyResponse = $app['buzz.browser']->get($scheme.'://'.$host.$uri);

    // TODO Handle 404 and other errors.
    // TODO Check content type...
    $image = $proxyResponse->getContent();

    // And transform...
    $box    = $request->headers->get('X-Painter-Box', '100x100');
    // TODO crop_face filter.
    $filter = $request->headers->get('X-Painter-Filter', 'thumbnail');

    list($width, $height) = explode('x', $box);

    $size = new Imagine\Image\Box($width, $height);

    $processedImage = $app['imagine']->load($image)->thumbnail($size);

    return new StreamedResponse(
        function() use($processedImage) {
            $processedImage->show('jpeg');
        }
    );
});
