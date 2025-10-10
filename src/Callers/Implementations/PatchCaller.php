<?php

namespace Raid\Caller\Callers\Implementations;

use Raid\Caller\Callers\CallerAbstract;

abstract readonly class PatchCaller extends CallerAbstract
{
    public function getMethod(): string
    {
        return 'PATCH';
    }
}
