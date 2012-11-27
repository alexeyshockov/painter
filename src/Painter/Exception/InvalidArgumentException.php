<?php

namespace Painter\Exception;

/**
 * @author Alexey Shockov <alexey@shockov.com>
 */
class InvalidArgumentException extends \InvalidArgumentException
{
    public function __construct($message, \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
