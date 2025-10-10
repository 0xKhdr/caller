<?php

namespace Raid\Caller\Services\Implementations;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Raid\Caller\Callers\Contracts\Caller;
use Raid\Caller\Receivers\Contracts\Receiver;
use Raid\Caller\Services\CallAbstract;
use Raid\Caller\Services\Support\CacheKey;

class CacheCallService extends CallAbstract
{
    public static function make(): self
    {
        return new self(
            config: config('caller.cache', [])
        );
    }

    /**
     * @throws ConnectionException
     */
    public function call(Caller $caller): Receiver
    {
        /** @var class-string<Receiver> $receiver */
        $receiver = $caller->getReceiver();

        $method = strtoupper($caller->getMethod());
        $url = $caller->getUrl();
        $options = $caller->getOptions();

        $shouldUseCache = $this->shouldUseCache(
            method: $method,
            callerConfig: Arr::get($options, 'caller', [])
        );

        if ($shouldUseCache) {
            $maybe = $this->get($method, $url, $options);
            if ($maybe !== null) {
                return $receiver::fromResponse($maybe);
            }
        }

        $response = Http::send(method: $method, url: $url, options: $options);
        if ($shouldUseCache) {
            $this->put($method, $url, $options, $response);
        }

        return $receiver::fromResponse($response);
    }

    public function isEnabled(): bool
    {
        return (bool) $this->fromConfig('enabled', false);
    }

    public function shouldUseCache(string $method, array $callerConfig): bool
    {
        return strtoupper($method) === 'GET'
            && ($this->isEnabled() || (Arr::get($callerConfig, 'cache', false)));
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
}
