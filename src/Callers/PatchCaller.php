<?php

namespace Raid\Caller\Callers;

abstract readonly class PatchCaller extends CallerAbstract
{
    public function getMethod(): string
    {
        return 'PATCH';
    }
}
