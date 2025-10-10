<?php

namespace Raid\Caller\Middleware;

use Closure;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RetryMiddleware
{
    private int $maxRetries;

    private int $delay;

    private ?Closure $retryCondition;

    private array $retryableStatusCodes;

    public function __construct(
        int $maxRetries = 3,
        int $delay = 1000,
        ?callable $retryCondition = null,
        array $retryableStatusCodes = [500, 502, 503, 504]
    ) {
        $this->maxRetries = $maxRetries;
        $this->delay = $delay;
        $this->retryCondition = $retryCondition;
        $this->retryableStatusCodes = $retryableStatusCodes;
    }

    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        return $this->retry($request, $next, $this->maxRetries);
    }

    private function retry(RequestInterface $request, callable $next, int $retriesLeft)
    {
        try {
            $response = $next($request);

            if ($this->shouldRetryResponse($response) && $retriesLeft > 0) {
                return $this->retryWithDelay($request, $next, $retriesLeft - 1);
            }

            return $response;

        } catch (ConnectException $e) {
            // Always retry connection exceptions
            if ($retriesLeft > 0) {
                return $this->retryWithDelay($request, $next, $retriesLeft - 1);
            }
            throw $e;
        } catch (RequestException $e) {
            if ($this->shouldRetryException($e) && $retriesLeft > 0) {
                return $this->retryWithDelay($request, $next, $retriesLeft - 1);
            }
            throw $e;
        }
    }

    private function retryWithDelay(RequestInterface $request, callable $next, int $retriesLeft)
    {
        usleep($this->delay * 1000);

        return $this->retry($request, $next, $retriesLeft);
    }

    private function shouldRetryResponse(ResponseInterface $response): bool
    {
        if ($this->retryCondition) {
            return ($this->retryCondition)($response);
        }

        return in_array($response->getStatusCode(), $this->retryableStatusCodes);
    }

    private function shouldRetryException(RequestException $e): bool
    {
        $response = $e->getResponse();

        if ($response) {
            return in_array($response->getStatusCode(), $this->retryableStatusCodes);
        }

        return false;
    }
}
