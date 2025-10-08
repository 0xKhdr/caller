<?php

namespace Raid\Caller\Receivers;

use Illuminate\Http\Client\Response;
use Raid\Caller\Traits\Arrayable;

abstract class ReceiverAbstract implements Contracts\Receiver
{
    use Arrayable;

    abstract public static function fromResponse(Response $response): static;
}
