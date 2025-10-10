<?php

namespace Raid\Caller\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Raid\Caller\Callers\Contracts\Caller;
use Raid\Caller\Receivers\Contracts\Receiver;
use Throwable;

class CallService implements Contracts\Call
{
    public static function make(): self
    {
        return new self;
    }

    public function call(Caller $caller): Receiver
    {
        /** @var Receiver $receiver */
        $receiver = $caller->getReceiver();

        $method = strtoupper($caller->getMethod());
        $url = $caller->getUrl();
        $options = $caller->getOptions();

        // Extract meta options for caller-level behaviors without sending them over HTTP
        $callerMeta = $options['caller'] ?? [];
        if (isset($options['caller'])) {
            unset($options['caller']);
        }

        $cfgHttp = config('caller.http', []);
        $cfgRetry = config('caller.retry', []);
        $cfgCache = config('caller.cache', []);

        $pending = Http::timeout((float) ($cfgHttp['timeout'] ?? 10.0))
            ->withOptions(['connect_timeout' => (float) ($cfgHttp['connect_timeout'] ?? 5.0)]);

        // Optional GET response caching delegated to CallCacheService
        $cache = new CallCacheService($cfgCache);
        $useCache = $cache->shouldUseCache($method, $callerMeta);
        if ($useCache) {
            $maybe = $cache->get($method, $url, $options);
            if ($maybe instanceof Response) {
                return $receiver::fromResponse($maybe);
            }
        }

        // Manual retry loop to support exponential backoff with jitter and 429 handling
        $maxAttempts = (int) ($cfgRetry['max_attempts'] ?? 3);
        $attempt = 0;
        $lastResponse = null;
        while (true) {
            $attempt++;
            try {
                /** @var Response $response */
                $response = $pending->send(method: $method, url: $url, options: $options);
                $lastResponse = $response;

                // On 429/5xx, decide retry
                $status = $response->status();
                $shouldRetry = false;
                if (($cfgRetry['on_too_many_requests'] ?? true) && $status === 429) {
                    $shouldRetry = true;
                } elseif (($cfgRetry['on_server_errors'] ?? true) && $status >= 500 && $status < 600) {
                    $shouldRetry = true;
                }

                if (! $shouldRetry || $attempt >= $maxAttempts || ! ($cfgRetry['enabled'] ?? true)) {
                    // Optionally store GET success in cache
                    if ($useCache && $method === 'GET') {
                        $cache->put($method, $url, $options, $response);
                    }

                    return $receiver::fromResponse($response);
                }

                // Compute backoff delay (ms) with jitter, honor Retry-After if present
                $delay = (int) ($cfgRetry['base_delay_ms'] ?? 200);
                $exp = 2 ** max(0, $attempt - 1);
                $delay = min((int) (($delay) * $exp), (int) ($cfgRetry['max_delay_ms'] ?? 2000));

                $retryAfterHeader = $response->header('Retry-After');
                if (is_numeric($retryAfterHeader)) {
                    $delay = max($delay, (int) $retryAfterHeader * 1000);
                }

                if ($cfgRetry['jitter'] ?? true) {
                    $delay = random_int((int) ($delay * 0.5), $delay);
                }

                usleep($delay * 1000);
            } catch (Throwable $e) {
                $lastResponse = null;
                $shouldRetry = ($cfgRetry['enabled'] ?? true) && ($cfgRetry['on_connection_exception'] ?? true) && $attempt < $maxAttempts;
                if (! $shouldRetry) {
                    // Build synthetic 599 response for network errors
                    /** @var Response $response */
                    $response = Http::response(['message' => 'Network error', 'error' => get_class($e)], 599);

                    return $receiver::fromResponse($response);
                }

                $delay = (int) ($cfgRetry['base_delay_ms'] ?? 200);
                $exp = 2 ** max(0, $attempt - 1);
                $delay = min((int) (($delay) * $exp), (int) ($cfgRetry['max_delay_ms'] ?? 2000));
                if ($cfgRetry['jitter'] ?? true) {
                    $delay = random_int((int) ($delay * 0.5), $delay);
                }
                usleep($delay * 1000);
            }
        }
    }
}
