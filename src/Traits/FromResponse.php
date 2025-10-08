<?php

namespace Raid\Caller\Traits;

use Illuminate\Http\Client\Response;

trait FromResponse
{
    abstract public static function fromResponse(Response $response): static;
}
