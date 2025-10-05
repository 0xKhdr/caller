<?php

namespace Raid\Caller\Dtos\Contracts;

interface RequestDto
{
    public function getMethod(): string;

    public function getUrl(): string;

    public function getOptions(): array;

    public function getResponseDto(): string;
}
