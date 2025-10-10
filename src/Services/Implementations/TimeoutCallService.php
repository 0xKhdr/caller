<?php

namespace Raid\Caller\Services\Implementations;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Raid\Caller\Callers\Contracts\Caller;
use Raid\Caller\Receivers\Contracts\Receiver;
use Raid\Caller\Services\CallAbstract;

class TimeoutCallService extends CallAbstract
{
    /**
     * @throws ConnectionException
     */
    public function call(Caller $caller): Receiver
    {
        /** @var class-string<Receiver> $receiver */
        $receiver = $caller->getReceiver();

        $cfg = config('caller.http', []);

        $pending = Http::timeout((float) ($cfg['timeout'] ?? 10.0))
            ->withOptions(['connect_timeout' => (float) ($cfg['connect_timeout'] ?? 5.0)]);

        return $receiver::fromResponse(
            $pending->send(
                method: strtoupper($caller->getMethod()),
                url: $caller->getUrl(),
                options: $caller->getOptions(),
            )
        );
    }
}
