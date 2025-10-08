<?php

namespace Raid\Caller\Traits;

trait ToResponse
{
    protected readonly int $status;

    public function toResponse(): array
    {
        return $this->isSuccessResponse()
            ? $this->toSuccessResponse()
            : $this->toErrorResponse();
    }

    public function toErrorResponse(): array
    {
        return [];
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
