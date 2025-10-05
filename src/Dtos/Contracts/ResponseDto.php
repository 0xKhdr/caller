<?php

namespace Raid\Caller\Dtos\Contracts;

use Illuminate\Http\Client\Response;

interface ResponseDto
{
    public static function fromCall(Response $response): static;
}
