<?php

namespace Raid\Caller\Callers;

abstract readonly class DeleteCaller extends CallerAbstract
{
    public function getMethod(): string
    {
        return 'DELETE';
    }
}
