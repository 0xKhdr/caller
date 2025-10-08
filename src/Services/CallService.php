<?php

namespace Raid\Caller\Services;

use Illuminate\Support\Facades\Http;
use Raid\Caller\Callers\Contracts\Caller;
use Raid\Caller\Receivers\Contracts\Receiver;

class CallService implements Contracts\Call
{
    public static function make(): self
    {
        return new self;
    }

    public function call(Caller $caller): Receiver
    {
        /** @var Receiver $receiver */
        $receiver = $caller->getReceiver();
        return $receiver::fromResponse(
            Http::send(
                method: $caller->getMethod(),
                url: $caller->getUrl(),
                options: $caller->getOptions(),
            )
        );
    }
}
