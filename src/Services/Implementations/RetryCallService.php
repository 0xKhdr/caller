<?php

namespace Raid\Caller\Services\Implementations;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Raid\Caller\Services\CallAbstract;
use Random\RandomException;
use Throwable;
use Raid\Caller\Callers\Contracts\Caller;
use Raid\Caller\Receivers\Contracts\Receiver;
use Raid\Caller\Services\Contracts\Call as CallContract;

class RetryCallService extends CallAbstract
{
    /**
     * @throws RandomException
     */
    public function call(Caller $caller): Receiver
    {
        /** @var class-string<Receiver> $receiver */
        $receiver = $caller->getReceiver();

        $method = strtoupper($caller->getMethod());
        $url = $caller->getUrl();
        $options = $caller->getOptions();

        $pending = Http::asJson();
        $cfg = config('caller.retry', []);
        $maxAttempts = (int)($cfg['max_attempts'] ?? 3);

        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                /** @var Response $response */
                $response = $pending->send(method: $method, url: $url, options: $options);

                $status = $response->status();
                $shouldRetry = false;
                if (($cfg['on_too_many_requests'] ?? true) && $status === 429) {
                    $shouldRetry = true;
                } elseif (($cfg['on_server_errors'] ?? true) && $status >= 500 && $status < 600) {
                    $shouldRetry = true;
                }

                if (!$shouldRetry || $attempt >= $maxAttempts || !($cfg['enabled'] ?? true)) {
                    return $receiver::fromResponse($response);
                }

                $delay = (int)($cfg['base_delay_ms'] ?? 200);
                $exp = 2 ** max(0, $attempt - 1);
                $delay = min((int)($delay * $exp), (int)($cfg['max_delay_ms'] ?? 2000));
                $retryAfter = $response->header('Retry-After');
                if (is_numeric($retryAfter)) {
                    $delay = max($delay, (int)$retryAfter * 1000);
                }
                if ($cfg['jitter'] ?? true) {
                    $delay = random_int((int)($delay * 0.5), $delay);
                }
                usleep($delay * 1000);
            } catch (Throwable $e) {
                $shouldRetry = ($cfg['enabled'] ?? true) && ($cfg['on_connection_exception'] ?? true) && $attempt < $maxAttempts;
                if (!$shouldRetry) {
                    /** @var Response $response */
                    $response = Http::response(['message' => 'Network error', 'error' => get_class($e)], 599);
                    return $receiver::fromResponse($response);
                }
                $delay = (int)($cfg['base_delay_ms'] ?? 200);
                $exp = 2 ** max(0, $attempt - 1);
                $delay = min((int)($delay * $exp), (int)($cfg['max_delay_ms'] ?? 2000));
                if ($cfg['jitter'] ?? true) {
                    $delay = random_int((int)($delay * 0.5), $delay);
                }
                usleep($delay * 1000);
            }
        }
    }
}


