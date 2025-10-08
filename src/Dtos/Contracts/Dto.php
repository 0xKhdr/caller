<?php

namespace Raid\Caller\Dtos\Contracts;

use Illuminate\Http\Client\Response;

interface Dto
{
    public static function fromResponse(Response $response): static;

    public function toArray(): array;

    public function has(string $key): bool;

    public function get(string $key, mixed $default = null): mixed;
}
