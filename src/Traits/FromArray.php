<?php

namespace Raid\Caller\Traits;

trait FromArray
{
    abstract public static function fromArray(array $data): static;
}
