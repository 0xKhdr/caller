<?php

declare(strict_types=1);

namespace Raid\Caller\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Raid\Caller\Services\Support\CacheKey;

readonly class CallCacheService
{
    public function __construct(
        protected array $config
    ) {}

    public static function make(): self
    {
        return new self(config('caller.cache', []));
    }

    public function isEnabled(): bool
    {
        return (bool) $this->fromConfig('enabled', false);
    }

    public function shouldUseCache(string $method, array $callerMeta): bool
    {
        return strtoupper($method) === 'GET'
            && ($this->isEnabled() || (Arr::get($callerMeta, 'cache', false)));
    }

    public function keyFor(string $method, string $url, array $options): string
    {
        return CacheKey::forRequest($method, $url, $options);
    }

    public function get(string $method, string $url, array $options): ?PromiseInterface
    {
        $key = $this->keyFor($method, $url, $options);
        $cached = Cache::get($key);
        if (is_array($cached) && isset($cached['status'], $cached['body'], $cached['headers'])) {
            return Http::response($cached['body'], (int) $cached['status'], $cached['headers']);
        }

        return null;
    }

    public function put(string $method, string $url, array $options, Response $response): void
    {
        $status = $response->status();
        if ($status < 200 || $status >= 300) {
            return;
        }

        $key = $this->keyFor($method, $url, $options);
        $headers = $response->headers();
        $ttlSeconds = (int) ($this->fromConfig('ttl_seconds', 60));

        Cache::put($key, [
            'status' => $status,
            'body' => $response->json() ?? $response->body(),
            'headers' => $headers,
        ], $ttlSeconds);
    }

    protected function fromConfig(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }
}
