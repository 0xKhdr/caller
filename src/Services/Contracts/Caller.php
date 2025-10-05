<?php

namespace Raid\Caller\Services\Contracts;

use Raid\Caller\Dtos\Contracts\RequestDto;
use Raid\Caller\Dtos\Contracts\ResponseDto;

interface Caller
{
    public function call(RequestDto $requestDto): ResponseDto;
}
