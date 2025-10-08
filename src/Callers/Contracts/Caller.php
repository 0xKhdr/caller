<?php

namespace Raid\Caller\Callers\Contracts;

use Raid\Caller\Receivers\Contracts\Receiver;

interface Caller
{
    public function getMethod(): string;

    public function getUrl(): string;

    public function getOptions(): array;

    public function getReceiver(): string;

    public function call(): Receiver;

    public function log(): void;
}
