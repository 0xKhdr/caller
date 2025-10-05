<?php

namespace Raid\Caller\Services;

use Illuminate\Support\Facades\Http;
use Raid\Caller\Dtos\Contracts\RequestDto;
use Raid\Caller\Dtos\Contracts\ResponseDto;

class CallerService implements Contracts\Caller
{
    public static function make(): self
    {
        return new self;
    }

    public function call(RequestDto $requestDto): ResponseDto
    {
        /** @var ResponseDto $responseDto */
        $responseDto = $requestDto->getResponseDto();

        return $responseDto::fromCall(
            Http::send(
                method: $requestDto->getMethod(),
                url: $requestDto->getUrl(),
                options: $requestDto->getOptions(),
            )
        );
    }
}
