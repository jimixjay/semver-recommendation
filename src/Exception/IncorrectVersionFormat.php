<?php

namespace Jimixjay\Exceptions;

use Exception;
use Throwable;

class IncorrectVersionFormat extends Exception
{
    public function __construct(string $lastVersion, Throwable $previous = null)
    {
        parent::__construct('Version format is not correct (x.y.z expected) <' . $lastVersion . ' received>', 501, $previous);
    }
}