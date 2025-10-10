<?php

namespace Raid\Caller\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Raid\Caller\Callers\Contracts\Caller;
use Raid\Caller\Receivers\Contracts\Receiver;
use Throwable;

abstract class CallAbstract implements Contracts\Call
{
}
