<?php

namespace Raid\Caller\Callers\Implementations;

use Raid\Caller\Callers\CallerAbstract;

abstract readonly class GetCaller extends CallerAbstract
{
    public function getMethod(): string
    {
        return 'GET';
    }
}
