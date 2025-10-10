<?php

namespace Raid\Caller\Receivers;

use Illuminate\Support\Facades\Log;
use JsonException;
use Raid\Caller\Traits\ToArray;

abstract readonly class ReceiverAbstract implements Contracts\Receiver, Contracts\ToArray, Contracts\ToLog
{
    use ToArray;

    /**
     * @throws JsonException
     */
    public function toLog(): static
    {
        Log::log(
            level: 'info',
            message: sprintf(
                'Received response of type %s with these data: %s',
                static::class,
                json_encode(get_object_vars($this), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            ),
            context: [
                'receiver' => static::class,
                'data' => get_object_vars($this),
            ]
        );

        return $this;
    }
}
