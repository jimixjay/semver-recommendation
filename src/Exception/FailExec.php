<?php

namespace Jimixjay\SemverRecommendation\Exception;

use Exception;
use Throwable;

class FailExec extends Exception
{
    public function __construct(string $msg, int $code, Throwable $previous = null)
    {
        parent::__construct($msg, $code, $previous);
    }
}