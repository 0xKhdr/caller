<?php

namespace Raid\Caller\Dtos;

use Illuminate\Http\Client\Response;

abstract class ResponseDtoAbstract implements Contracts\ResponseDto
{
    abstract public static function fromResponse(Response $response): static;

    public function toArray(): array
    {
        return [];
    }
}
