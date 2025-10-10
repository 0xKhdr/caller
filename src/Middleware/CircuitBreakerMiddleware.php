<?php

namespace Raid\Caller\Middleware;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Raid\Caller\Exceptions\CircuitBreakerException;
use RuntimeException;
use Throwable;

class CircuitBreakerMiddleware
{
    private CacheRepository $cache;

    private string $serviceName;

    private int $failureThreshold;

    private int $timeout;

    private int $halfOpenTimeout;

    public function __construct(
        string $serviceName,
        int $failureThreshold = 5,
        int $timeout = 60,
        int $halfOpenTimeout = 30,
        ?CacheRepository $cache = null
    ) {
        $this->cache = $cache ?? app('cache');
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold;
        $this->timeout = $timeout;
        $this->halfOpenTimeout = $halfOpenTimeout;
    }

    /**
     * @throws CircuitBreakerException|Throwable
     */
    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        $state = $this->getState();

        if ($state['status'] === 'open') {
            if (time() - $state['lastFailure'] < $this->timeout) {
                throw new CircuitBreakerException(
                    "Circuit breaker is OPEN for service: $this->serviceName"
                );
            } else {
                // Transition to half-open
                $this->setState('half-open', $state['failures']);
            }
        }

        try {
            $response = $next($request);

            if ($response->getStatusCode() >= 500) {
                $this->recordFailure();
                throw new RuntimeException("Server error: {$response->getStatusCode()}");
            }

            $this->recordSuccess();

            return $response;

        } catch (Throwable $e) {
            $this->recordFailure();
            throw $e;
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    private function getState(): array
    {
        return $this->cache->get($this->getStateKey(), [
            'status' => 'closed',
            'failures' => 0,
            'lastFailure' => 0,
            'lastSuccess' => 0,
        ]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    private function setState(string $status, int $failures = 0): void
    {
        $state = [
            'status' => $status,
            'failures' => $failures,
            'lastFailure' => time(),
            'lastSuccess' => $status === 'closed' ? time() : $this->getState()['lastSuccess'],
        ];

        $this->cache->put($this->getStateKey(), $state, $this->timeout * 2);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    private function recordFailure(): void
    {
        $state = $this->getState();
        $state['failures']++;
        $state['lastFailure'] = time();

        if ($state['failures'] >= $this->failureThreshold) {
            $state['status'] = 'open';
        } elseif ($state['status'] === 'half-open') {
            $state['status'] = 'open'; // Single failure in half-open opens the circuit
        }

        $this->cache->put($this->getStateKey(), $state, $this->timeout * 2);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    private function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state['status'] === 'half-open') {
            // Success in half-open state closes the circuit
            $state['status'] = 'closed';
            $state['failures'] = 0;
        } else {
            // Reset failure count on consecutive successes
            $state['failures'] = max(0, $state['failures'] - 1);
        }

        $state['lastSuccess'] = time();
        $this->cache->put($this->getStateKey(), $state, $this->timeout * 2);
    }

    private function getStateKey(): string
    {
        return "circuit_breaker:$this->serviceName";
    }
}
