<?php

namespace Painter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Silex\Application;

use Buzz\Browser;

/**
 * @author Alexey Shockov <alexey@shockov.com>
 */
class Proxy
{
    /**
     * @var \Buzz\Browser
     */
    private $browser;

    /**
     * @var string
     */
    private $name;

    /**
     * @param \Buzz\Browser $browser
     * @param string        $name
     */
    public function __construct(Browser $browser, $name)
    {
        $this->browser = $browser;
        $this->name    = $name;
    }

    /**
     * @param \Exception $exception
     * @param int        $code
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleError(\Exception $exception, $code)
    {
        // TODO Log (error log).

        // TODO Process 415 (type exception)...

        return new Response($exception->getMessage(), 500);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param callback                                  $processor
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function call(Request $request, $processor = null)
    {
        // TODO Check $processor.

        $scheme = $request->headers->get('X-Proxy-Scheme', 'http');
        $ip     = $request->headers->get('X-Proxy-IP', null);
        $host   = $request->headers->get('X-Proxy-Host', 'localhost');
        $uri    = $request->getRequestUri();

        if (!$host) {
            throw new \InvalidArgumentException('Host isn\'t set.');
        }

        // TODO Remove Painter from here...
        $proxyHeaders = [];
        foreach ($request->headers->all() as $name => $values) {
            if (in_array($name, [
                'host',
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

            foreach ($values as $value) {
                $proxyHeaders[] = $name.': '.$value;
            }
        }

        $proxyHeaders[] = 'Host: '.$host;
        $proxyHeaders[] = 'X-Forwarded-For: '.$request->getClientIp();

        if ($ip) {
            $host = $ip;
        }

        /** @var \Buzz\Message\Response */
        $proxyResponse = $this->browser->call(
            $scheme.'://'.$host.$uri,
            $request->getMethod(),
            $proxyHeaders,
            $request->getContent());

        // TODO Other 20x and 30x codes.
        if ($proxyResponse->getStatusCode() == 200) {
            $response = call_user_func($processor, $request, $this->browser->getLastRequest(), $proxyResponse);
        } else {
            // TODO Log (access log).

            $response = new Response($proxyResponse->getContent(), $proxyResponse->getStatusCode());
        }

        $headers = [];
        foreach ($proxyResponse->getHeaders() as $header) {
            if (strpos($header, ':') === false) {
                continue;
            }

            list($name, $value) = explode(': ', $header, 2);

            if (in_array($name, [
                'date',
                'content-type',
                // TODO Include original length for "original" (error) responses.
                'content-length',
                'connection'
            ])) {
                continue;
            }

            if (isset($headers[$name])) {
                $headers[$name][] = $value;
            } else {
                $headers[$name] = [$value];
            }
        }

        $response->headers->add($headers);

        $response->headers->set('Via', $this->name, false);

        return $response;
    }
}
