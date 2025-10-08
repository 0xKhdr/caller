<?php

namespace Raid\Caller\Services\Contracts;

use Raid\Caller\Callers\Contracts\Caller;
use Raid\Caller\Receivers\Contracts\Receiver;

interface Call
{
    public function call(Caller $caller): Receiver;
}
