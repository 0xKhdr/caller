<?php

use Raid\Caller\Services\Implementations\CacheCallService;
use Raid\Caller\Services\Implementations\RetryCallService;
use Raid\Caller\Services\Implementations\SimpleCallService;
use Raid\Caller\Services\Implementations\TimeoutCallService;

return [
    // Default call service strategy
    'service' => 'simple',

    // Map of strategy keys to implementation classes
    'services' => [
        'simple' => SimpleCallService::class,
        'timeout' => TimeoutCallService::class,
        'retry' => RetryCallService::class,
        'cache' => CacheCallService::class,
    ],

    'http' => [
        // Total request timeout in seconds
        'timeout' => 10.0,
        // Connect timeout in seconds
        'connect_timeout' => 5.0,
    ],

    'retry' => [
        'enabled' => true,
        // Total attempts including the first one
        'max_attempts' => 3,
        // Base delay in milliseconds (exponential backoff applies)
        'base_delay_ms' => 200,
        // Upper bound for delay in milliseconds
        'max_delay_ms' => 2000,
        // Add full jitter to backoff delay
        'jitter' => true,
        // Retry conditions
        'on_connection_exception' => true,
        'on_server_errors' => true, // 5xx
        'on_too_many_requests' => true, // 429
    ],

    'cache' => [
        // Global toggle; per-caller can opt-in via options['caller']['cache'] = true
        'enabled' => false,
        // TTL in seconds for successful GET responses
        'ttl_seconds' => 60,
    ],
];
