<?php

namespace Raid\Caller\Receivers;

use Raid\Caller\Traits\ToArray;
use Raid\Caller\Traits\ToLog;

abstract readonly class ReceiverAbstract implements Contracts\Receiver, Contracts\ToArray, Contracts\ToLog
{
    use ToArray;
    use ToLog;
}
