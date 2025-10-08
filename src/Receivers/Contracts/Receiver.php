<?php

namespace Raid\Caller\Receivers\Contracts;

use Illuminate\Http\Client\Response;

interface Receiver
{
    public static function fromResponse(Response $response): static;

    public function toArray(): array;
}
