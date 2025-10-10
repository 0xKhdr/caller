<?php

namespace Raid\Caller\Services;

use Illuminate\Support\Arr;

abstract class CallAbstract implements Contracts\Call
{
    protected array $config;

    protected function fromConfig(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }
}
