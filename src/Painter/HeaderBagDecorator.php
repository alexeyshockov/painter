<?php

namespace Painter;

/**
 * Normalize HTTP headers...
 *
 * @author Alexey Shockov <alexey@shockov.com>
 */
class HeaderBagDecorator
{
    private $headerBag;

    public function __construct($headerBag)
    {
        $this->headerBag = $headerBag;
    }

    public function all()
    {
        $lowerCaseHeaders = $this->headerBag->all();

        $headers = array();
        foreach ($lowerCaseHeaders as $name => $value) {
            $headers[$this->normalize($name)] = $value;
        }

        return $headers;
    }

    private function normalize($header)
    {
        $header = str_replace('- ', '-', ucwords(str_replace('-', '- ', $header)));

        // Exceptions.
        $exceptions = [
            'Etag'             => 'ETag',
            'Www-Authenticate' => 'WWW-Authenticate',
            'P3p'              => 'P3P',
        ];

        if (in_array($header, array_keys($exceptions))) {
            $header = $exceptions[$header];
        }

        return $header;
    }

    public function __call($method, $arguments)
    {
        if (is_callable(array($this->headerBag, $method))) {
            return call_user_func_array(
                array($this->headerBag, $method),
                $arguments
            );
        } else {
            throw new \BadMethodCallException();
        }
    }
}
