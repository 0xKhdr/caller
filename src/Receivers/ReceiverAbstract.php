<?php

namespace Raid\Caller\Receivers;

use Illuminate\Http\Client\Response;

abstract class ReceiverAbstract implements Contracts\Receiver
{
    abstract public static function fromResponse(Response $response): static;

    public function toArray(): array
    {
        return [];
    }
}
