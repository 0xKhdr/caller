<?php

namespace Raid\Caller\Dtos\Contracts;

interface Dto
{
    public static function fromArray(array $data): static;

    public function has(string $key): bool;

    public function get(string $key, mixed $default = null): mixed;
}
