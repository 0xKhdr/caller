<?php

namespace Raid\Caller\Receivers;

use Raid\Caller\Traits\ToArray;

abstract readonly class ReceiverAbstract implements Contracts\Receiver, Contracts\ToArray
{
    use ToArray;
}
