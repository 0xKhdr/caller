<?php

namespace Raid\Caller\Facades;

use Illuminate\Support\Facades\Facade;
use Raid\Caller\Callers\Contracts\Caller;

/**
 * @method static call(Caller $caller)
 */
class Call extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'call';
    }
}
