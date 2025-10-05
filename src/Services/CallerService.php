<?php

namespace Raid\Caller\Services;

class CallerService implements Contracts\Caller
{
    public static function make(): self
    {
        return new self();
    }
}