<?php

namespace Raid\Caller\Callers;

abstract readonly class GetCaller extends CallerAbstract
{
    public function getMethod(): string
    {
        return 'GET';
    }
}
