<?php

namespace Raid\Caller\Executors\Implementations;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Raid\Caller\Builders\Contracts\RequestBuilder as RequestBuilderContract;
use Raid\Caller\Exceptions\CallerException;
use Raid\Caller\Executors\Contracts\RequestExecutor as RequestExecutorContract;
use Raid\Caller\Executors\ExecutorAbstract;
use Raid\Caller\Middleware\CacheMiddleware;
use Raid\Caller\Middleware\CircuitBreakerMiddleware;
use Raid\Caller\Middleware\LoggingMiddleware;
use Raid\Caller\Middleware\MiddlewarePipeline;
use Raid\Caller\Middleware\RetryMiddleware;

class RequestExecutor extends ExecutorAbstract implements RequestExecutorContract
{
    private array $config;

    private array $metrics = [];

    private MiddlewarePipeline $middlewarePipeline;

    private PendingRequest $httpClient;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->middlewarePipeline = new MiddlewarePipeline;
        $this->httpClient = $this->createHttpClient();
    }

    /**
     * @throws GuzzleException
     * @throws CallerException
     */
    public function execute(RequestBuilderContract $builder): ResponseInterface
    {
        try {
            // Apply request middleware
            $request = $this->middlewarePipeline->processRequest(
                $this->buildPsr7Request($builder),
                $builder
            );

            // Execute the request
            $response = $this->sendHttpRequest($request, $builder->getOptions());

            // Apply response middleware
            return $this->middlewarePipeline->processResponse(
                $response,
                $builder
            );
        } catch (Exception $e) {
            throw CallerException::requestFailed($builder, $e);
        }
    }

    /**
     * @throws ConnectionException
     */
    public function executeAsync(RequestBuilderContract $builder): PromiseInterface
    {
        return $this->httpClient
            ->async()
            ->withOptions($this->buildRequestOptions($builder))
            ->send($builder->getMethod(), $builder->getUrl(), $this->buildRequestData($builder));
    }

    /**
     * @throws ConnectionException
     */
    public function executePool(array $builders): array
    {
        return array_map(function ($builder) {
            return $this->executeAsync($builder);
        }, $builders);
    }

    public function withMiddleware(callable|array $middleware): self
    {
        $this->middlewarePipeline->push($middleware);

        return $this;
    }

    public function withGlobalMiddleware(): self
    {
        $globalMiddleware = config('caller.middleware.global', []);

        foreach ($globalMiddleware as $middleware) {
            $this->middlewarePipeline->push($middleware);
        }

        return $this;
    }

    public function retry(int $times = 3, int $sleep = 1000, ?callable $when = null): self
    {
        return $this->withMiddleware(
            new RetryMiddleware($times, $sleep, $when)
        );
    }

    public function timeout(float $seconds): self
    {
        $this->httpClient->timeout($seconds);

        return $this;
    }

    public function withLogger($logger = null): self
    {
        return $this->withMiddleware(
            new LoggingMiddleware($logger)
        );
    }

    public function withCache(int $ttl = 3600, ?string $key = null): self
    {
        return $this->withMiddleware(
            new CacheMiddleware($ttl, $key)
        );
    }

    public function withCircuitBreaker(
        string $name,
        int $failureThreshold = 5,
        int $timeout = 60
    ): self {
        return $this->withMiddleware(
            new CircuitBreakerMiddleware($name, $failureThreshold, $timeout)
        );
    }

    private function createHttpClient(): PendingRequest
    {
        $client = Http::withOptions($this->config);

        // Apply global configuration
        if ($userAgent = config('caller.user_agent')) {
            $client->withUserAgent($userAgent);
        }

        if (config('caller.without_verifying', false)) {
            $client->withoutVerifying();
        }

        return $client;
    }

    private function buildPsr7Request(RequestBuilderContract $builder): RequestInterface
    {
        // Convert BuilderAbstract to PSR-7 Request
        return new Request(
            method: $builder->getMethod(),
            uri: $builder->getUrl(),
            headers: $builder->getHeaders(),
            body: $builder->getBody() ? json_encode($builder->getBody()) : null
        );
    }

    /**
     * @throws GuzzleException
     */
    private function sendHttpRequest(RequestInterface $request, array $options = []): ResponseInterface
    {
        $client = new Client($options);

        return $client->send($request, $options);
    }

    private function buildRequestOptions(RequestBuilderContract $builder): array
    {
        $options = array_merge($this->config, $builder->getOptions());

        // Add query parameters
        if ($query = $builder->getQuery()) {
            $options['query'] = $query;
        }

        // Add cookies
        if ($cookies = $builder->getCookies()) {
            $options['cookies'] = $cookies;
        }

        return $options;
    }

    private function buildRequestData(RequestBuilderContract $builder): array
    {
        $data = [];

        // Add form/data parameters
        if ($builder->getMethod() === 'POST' && $builder->getBody()) {
            $data[$builder->isJson() ? 'json' : 'form_params'] = $builder->getBody();
        }

        // Add multipart data
        if ($builder->hasFiles()) {
            $data['multipart'] = $builder->getFiles();
        }

        return $data;
    }

    public function getMetrics(): array
    {
        return [
            'total_requests' => $this->metrics['total_requests'] ?? 0,
            'failed_requests' => $this->metrics['failed_requests'] ?? 0,
            'average_response_time' => $this->metrics['average_response_time'] ?? 0,
        ];
    }

    public function resetMetrics(): void
    {
        $this->metrics = [];
    }

    /**
     * @throws GuzzleException
     * @throws CallerException
     */
    public function call(RequestBuilderContract $builder): ResponseInterface
    {
        return $this->execute($builder);
    }

    /**
     * @throws ConnectionException
     */
    public function callAsync(RequestBuilderContract $builder): PromiseInterface
    {
        return $this->executeAsync($builder);
    }

    public function withRequestMiddleware(callable $middleware): static
    {
        // TODO: Implement withRequestMiddleware() method.
        return $this;
    }

    public function withResponseMiddleware(callable $middleware): static
    {
        // TODO: Implement withResponseMiddleware() method.
        return $this;
    }

    /**
     * @throws GuzzleException
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->sendHttpRequest($request);
    }
}
