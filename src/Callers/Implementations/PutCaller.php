<?php

namespace Raid\Caller\Callers\Implementations;

use Raid\Caller\Callers\CallerAbstract;

abstract readonly class PutCaller extends CallerAbstract
{
    public function getMethod(): string
    {
        return 'PUT';
    }
}
