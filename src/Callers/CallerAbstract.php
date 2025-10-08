<?php

namespace Raid\Caller\Callers;

use Raid\Caller\Receivers\Contracts\Receiver;
use Raid\Caller\Services\CallService;
use Raid\Caller\Traits\ToArray;

abstract readonly class CallerAbstract implements Contracts\Caller
{
    use ToArray;

    abstract public function getMethod(): string;

    abstract public function getUrl(): string;

    abstract public function getReceiver(): string;

    public function getOptions(): array
    {
        return [];
    }

    public function call(): Receiver
    {
        return CallService::make()->call($this);
    }

    public function log(): void
    {
        // Implement logging logic here
    }
}
