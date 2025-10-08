<?php

namespace Raid\Caller\Callers;

abstract class GetCaller extends CallerAbstract
{
    public function getMethod(): string
    {
        return 'GET';
    }
}
