<?php

namespace Raid\Caller\Callers;

abstract readonly class PutCaller extends CallerAbstract
{
    public function getMethod(): string
    {
        return 'PUT';
    }
}
