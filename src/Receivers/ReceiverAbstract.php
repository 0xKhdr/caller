<?php

namespace Raid\Caller\Receivers;

use Raid\Caller\Traits\Arrayable;
use Raid\Caller\Traits\Responsable;

abstract readonly class ReceiverAbstract implements Contracts\Receiver
{
    use Arrayable;
    use Responsable;

    public function __construct(
        protected int $status,
    ) {}

    public function toSuccessResponse(): array
    {
        return [];
    }

    public function toErrorResponse(): array
    {
        return [];
    }

    public function toResponse(): array
    {
        return $this->isSuccessResponse()
            ? $this->toSuccessResponse()
            : $this->toErrorResponse();
    }

    public function isSuccessResponse(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
