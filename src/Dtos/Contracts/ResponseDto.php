<?php

namespace Raid\Caller\Dtos\Contracts;

use Illuminate\Http\Client\Response;

interface ResponseDto
{
    public static function fromResponse(Response $response): static;

    public function toArray(): array;
}
