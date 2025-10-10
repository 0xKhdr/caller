<?php

namespace Raid\Caller\Support;

class Query
{
    public static function filterNulls(array $query): array
    {
        return array_values(
            array_filter(
                $query,
                static fn ($value) => $value !== null
            )
        );
    }
}
