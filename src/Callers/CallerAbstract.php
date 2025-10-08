<?php

namespace Raid\Caller\Callers;

use Raid\Caller\Receivers\Contracts\Receiver;
use Raid\Caller\Services\CallService;
use Raid\Caller\Traits\ToArray;
use Raid\Caller\Traits\ToLog;

abstract readonly class CallerAbstract implements Contracts\Caller, Contracts\ToArray, Contracts\ToLog
{
    use ToArray;
    use ToLog;

    public function call(): Receiver
    {
        return CallService::make()->call($this);
    }

    abstract public function getMethod(): string;

    abstract public function getUrl(): string;

    abstract public function getReceiver(): string;

    public function getOptions(): array
    {
        return [];
    }
}
