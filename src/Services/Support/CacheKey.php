<?php

namespace Raid\Caller\Services\Support;

class CacheKey
{
    /**
     * Build a normalized cache key from method, url and options.
     * Only method, url and sorted query parameters affect the key.
     */
    public static function forRequest(string $method, string $url, array $options): string
    {
        $method = strtoupper($method);
        $query = $options['query'] ?? [];
        if (is_array($query)) {
            ksort($query);
        }
        $normalized = json_encode([
            'm' => $method,
            'u' => $url,
            'q' => $query,
        ], JSON_UNESCAPED_SLASHES);

        return 'caller:get:'.md5($normalized);
    }
}
