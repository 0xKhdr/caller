<?php

namespace Raid\Caller\Services;

use Illuminate\Support\Arr;

abstract class CallAbstract implements Contracts\Call
{
    public function __construct(
        protected readonly array $config = []
    ) {}

    protected function fromConfig(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }
}
