<?php

namespace Raid\Caller\Callers;

abstract readonly class PostCaller extends CallerAbstract
{
    public function getMethod(): string
    {
        return 'POST';
    }
}
