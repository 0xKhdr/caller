<?php

namespace Raid\Caller\Callers;

use Illuminate\Support\Facades\Log;
use JsonException;
use Raid\Caller\Receivers\Contracts\Receiver;
use Raid\Caller\Services\Contracts\Call as CallContract;
use Raid\Caller\Traits\ToArray;

abstract readonly class CallerAbstract implements Contracts\Caller, Contracts\ToArray, Contracts\ToLog
{
    use ToArray;

    public function call(): Receiver
    {
        /** @var CallContract $service */
        $service = app(CallContract::class);

        return $service->call($this);
    }

    abstract public function getMethod(): string;

    abstract public function getUrl(): string;

    abstract public function getReceiver(): string;

    public function getOptions(): array
    {
        return [];
    }

    /**
     * @throws JsonException
     */
    public function toLog(): static
    {
        Log::log(
            level: 'info',
            message: sprintf(
                'Calling %s on %s with these options: %s',
                $this->getMethod(),
                $this->getUrl(),
                json_encode($this->getOptions(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            ),
            context: [
                'caller' => static::class,
                'data' => get_object_vars($this),
            ]
        );

        return $this;
    }
}
