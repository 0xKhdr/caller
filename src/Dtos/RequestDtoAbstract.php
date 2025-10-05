<?php

namespace Raid\Caller\Dtos;

use Raid\Caller\Dtos\Contracts\ResponseDto;
use Raid\Caller\Services\CallerService;

abstract class RequestDtoAbstract implements Contracts\RequestDto
{
    abstract public function getMethod(): string;

    abstract public function getUrl(): string;

    abstract public function getResponseDto(): string;

    public function getOptions(): array
    {
        return [];
    }

    public function call(): ResponseDto
    {
        return CallerService::make()->call($this);
    }
}
