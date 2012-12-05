<?php

namespace Painter\Tests;

use Silex\WebTestCase;

/**
 * @author Alexey Shockov <alexey@shockov.com>
 */
class PainterTest extends WebTestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../../src/app.php';

        $app['debug'] = true;

        $app['exception_handler']->disable();

        return $app;
    }

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function shouldProxy()
    {
        $client = $this->createClient();

        $client->request('GET', '/images/srpr/logo3w.png', array(), array(), array(
            'HTTP_X_PROXY_HOST' => 'www.google.com',
        ));

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('image/png', $client->getResponse()->headers->get('Content-Type'));
        $this->assertSame('max-age=691200, public', $client->getResponse()->headers->get('Cache-Control'));
    }
}
