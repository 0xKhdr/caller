<?php

namespace Raid\Caller\Callers\Contracts;

use Raid\Caller\Receivers\Contracts\Receiver;

interface Caller
{
    public function call(): Receiver;

    public function getMethod(): string;

    public function getUrl(): string;

    public function getOptions(): array;

    public function getReceiver(): string;
}
