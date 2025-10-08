<?php

namespace Raid\Caller\Dtos;

use Raid\Caller\Traits\Arrayable;
use Raid\Caller\Traits\Responsable;

abstract class DtoAbstract implements Contracts\Dto
{
    use Arrayable;
    use Responsable;

    public function has(string $key): bool
    {
        return isset($this->$key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->has($key) ? $this->$key : $default;
    }
}
