<?php

namespace Raid\Caller\Traits;

use Illuminate\Support\Facades\Log;
use JsonException;

trait ToLog
{
    /**
     * @throws JsonException
     */
    public function toLog(): static
    {
        Log::info(
            message: sprintf(
                'Log from %s: encoded: %s',
                static::class,
                json_encode(get_object_vars($this), JSON_THROW_ON_ERROR)
            )
        );

        return $this;
    }
}
