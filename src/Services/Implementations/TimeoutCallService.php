<?php

namespace Raid\Caller\Services\Implementations;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Raid\Caller\Callers\Contracts\Caller;
use Raid\Caller\Receivers\Contracts\Receiver;
use Raid\Caller\Services\CallAbstract;

class TimeoutCallService extends CallAbstract
{
    public static function make(): self
    {
        return new self(
            config: config('caller.http', [])
        );
    }

    /**
     * @throws ConnectionException
     */
    public function call(Caller $caller): Receiver
    {
        /** @var class-string<Receiver> $receiver */
        $receiver = $caller->getReceiver();

        $timeout = (float) ($this->fromConfig('timeout', 10.0));
        $connectTimeout = (float) ($this->fromConfig('connect_timeout', 5.0));

        $pending = Http::timeout($timeout)
            ->withOptions(['connect_timeout' => $connectTimeout]);

        return $receiver::fromResponse(
            $pending->send(
                method: strtoupper($caller->getMethod()),
                url: $caller->getUrl(),
                options: $caller->getOptions(),
            )
        );
    }
}
