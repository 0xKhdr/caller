<?php

namespace Raid\Caller\Dtos\Contracts;

interface Dto
{
    public function toArray(): array;

    public function has(string $key): bool;

    public function get(string $key, mixed $default = null): mixed;
}
