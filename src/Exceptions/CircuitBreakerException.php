<?php

namespace Raid\Caller\Exceptions;

use Exception;

class CircuitBreakerException extends Exception
{
    public function __construct(string $message = 'Circuit breaker is open.', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
