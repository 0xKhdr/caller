<?php

namespace Raid\Caller\Receivers;

use Raid\Caller\Traits\ToResponse;

abstract readonly class ResponseReceiver extends ReceiverAbstract implements Contracts\ToResponse
{
    use ToResponse;
}
