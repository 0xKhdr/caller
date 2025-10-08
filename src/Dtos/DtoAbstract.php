<?php

namespace Raid\Caller\Dtos;

use Raid\Caller\Traits\ToArray;

abstract readonly class DtoAbstract implements Contracts\Dto, Contracts\ToArray
{
    use ToArray;

    public function has(string $key): bool
    {
        return isset($this->$key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->has($key) ? $this->$key : $default;
    }
}
