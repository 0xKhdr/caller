<?php

namespace Raid\Caller\Exceptions;

use Exception;
use Raid\Caller\Builders\Contracts\Builder;

class CallerException extends Exception
{
    public static function requestFailed(Builder $builder, ?Exception $previous = null): self
    {
        return new self(
            sprintf(
                'Request failed for %s %s',
                $builder->getMethod(),
                $builder->getUrl()
            ),
            0,
            $previous
        );
    }
}
