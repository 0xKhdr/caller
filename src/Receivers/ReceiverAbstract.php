<?php

namespace Raid\Caller\Receivers;

use Raid\Caller\Traits\Arrayable;
use Raid\Caller\Traits\FromResponse;
use Raid\Caller\Traits\ToResponse;

abstract readonly class ReceiverAbstract implements Contracts\Receiver
{
    use Arrayable;
    use FromResponse;
    use ToResponse;
}
