<?php

namespace Raid\Caller\Services\Implementations;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Raid\Caller\Callers\Contracts\Caller;
use Raid\Caller\Receivers\Contracts\Receiver;
use Raid\Caller\Services\CallAbstract;
use Random\RandomException;
use Throwable;

class RetryCallService extends CallAbstract
{
    public static function make(): self
    {
        return new self(
            config: config('caller.retry', [])
        );
    }

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

        $maxAttempts = (int) $this->fromConfig('max_attempts', 3);

        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                /** @var Response $response */
                $response = $pending->send(method: $method, url: $url, options: $options);

                $status = $response->status();
                $shouldRetry = false;
                if ($this->fromConfig('on_too_many_requests', true) && $status === 429) {
                    $shouldRetry = true;
                } elseif ($this->fromConfig('on_server_errors', true) && $status >= 500 && $status < 600) {
                    $shouldRetry = true;
                }

                if (! $shouldRetry || $attempt >= $maxAttempts || ! $this->fromConfig('enabled', true)) {
                    return $receiver::fromResponse($response);
                }

                $delay = (int) $this->fromConfig('base_delay_ms', 200);
                $exp = 2 ** max(0, $attempt - 1);
                $delay = min((int) ($delay * $exp), (int) $this->fromConfig('max_delay_ms', 2000));
                $retryAfter = $response->header('Retry-After');
                if (is_numeric($retryAfter)) {
                    $delay = max($delay, (int) $retryAfter * 1000);
                }
                if ($this->fromConfig('jitter', true)) {
                    $delay = random_int((int) ($delay * 0.5), $delay);
                }
                usleep($delay * 1000);
            } catch (Throwable $e) {
                $shouldRetry = $this->fromConfig('enabled', true) &&
                    $this->fromConfig('on_connection_exception', true) &&
                    $attempt < $maxAttempts;
                if (! $shouldRetry) {
                    /** @var Response $response */
                    $response = Http::response(['message' => 'Network error', 'error' => get_class($e)], 599);

                    return $receiver::fromResponse($response);
                }
                $delay = (int) $this->fromConfig('base_delay_ms', 200);
                $exp = 2 ** max(0, $attempt - 1);
                $delay = min((int) ($delay * $exp), (int) $this->fromConfig('max_delay_ms', 2000));
                if ($this->fromConfig('jitter', true)) {
                    $delay = random_int((int) ($delay * 0.5), $delay);
                }
                usleep($delay * 1000);
            }
        }
    }
}
