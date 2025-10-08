<?php

namespace Raid\Caller\Receivers;

use Raid\Caller\Traits\Arrayable;
use Raid\Caller\Traits\Responsable;

abstract class ReceiverAbstract implements Contracts\Receiver
{
    use Arrayable;
    use Responsable;
}
