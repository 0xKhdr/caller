<?php

namespace Raid\Caller\Services\Implementations;

use Illuminate\Support\Facades\Http;
use Raid\Caller\Callers\Contracts\Caller;
use Raid\Caller\Receivers\Contracts\Receiver;
use Raid\Caller\Services\CallAbstract;

class SimpleCallService extends CallAbstract
{
    public function call(Caller $caller): Receiver
    {
        /** @var class-string<Receiver> $receiver */
        $receiver = $caller->getReceiver();

        return $receiver::fromResponse(
            response: Http::send(
                method: strtoupper($caller->getMethod()),
                url: $caller->getUrl(),
                options: $caller->getOptions(),
            )
        );
    }
}
