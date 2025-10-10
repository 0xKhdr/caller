<?php

namespace Raid\Caller\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Raid\Caller\Builders\Contracts\RequestBuilder as RequestBuilderContract;
use Throwable;

class MiddlewarePipeline
{
    protected array $middlewares = [];

    protected array $requestMiddlewares = [];

    protected array $responseMiddlewares = [];

    protected array $errorMiddlewares = [];

    public function __construct(array $middlewares = [])
    {
        foreach ($middlewares as $middleware) {
            $this->push($middleware);
        }
    }

    // ==================== MIDDLEWARE REGISTRATION ====================

    public function push(callable|string|array $middleware): self
    {
        if (is_string($middleware)) {
            $middleware = app($middleware);
        }

        if (is_array($middleware)) {
            foreach ($middleware as $m) {
                $this->push($m);
            }

            return $this;
        }

        $this->middlewares[] = $middleware;

        return $this;
    }

    public function pushRequest(callable $middleware): self
    {
        $this->requestMiddlewares[] = $middleware;

        return $this;
    }

    public function pushResponse(callable $middleware): self
    {
        $this->responseMiddlewares[] = $middleware;

        return $this;
    }

    public function pushError(callable $middleware): self
    {
        $this->errorMiddlewares[] = $middleware;

        return $this;
    }

    public function pipe(callable|string $middleware): self
    {
        return $this->push($middleware);
    }

    public function unshift(callable|string $middleware): self
    {
        if (is_string($middleware)) {
            $middleware = app($middleware);
        }

        array_unshift($this->middlewares, $middleware);

        return $this;
    }

    // ==================== MIDDLEWARE EXECUTION ====================

    public function processRequest(RequestInterface $request, RequestBuilderContract $builder): RequestInterface
    {
        // Execute request-specific middlewares
        foreach ($this->requestMiddlewares as $middleware) {
            $request = $middleware($request, $builder);
        }

        // Execute general middlewares for request phase
        $handler = fn (RequestInterface $req) => $req;

        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            fn ($next, $middleware) => fn ($req) => $middleware($req, $next),
            $handler
        );

        return $pipeline($request);
    }

    public function processResponse(ResponseInterface $response, RequestBuilderContract $builder): ResponseInterface
    {
        // Execute response-specific middlewares
        foreach ($this->responseMiddlewares as $middleware) {
            $response = $middleware($response, $builder);
        }

        return $response;
    }

    /**
     * @throws Throwable
     */
    public function processError(Throwable $exception, RequestBuilderContract $builder): ?ResponseInterface
    {
        foreach ($this->errorMiddlewares as $middleware) {
            $result = $middleware($exception, $builder);

            // If middleware returns a response, use it
            if ($result instanceof ResponseInterface) {
                return $result;
            }

            // If middleware returns true, stop propagation
            if ($result === true) {
                return null;
            }
        }

        throw $exception;
    }

    /**
     * @throws Throwable
     */
    public function execute(RequestBuilderContract $builder, callable $handler): ResponseInterface
    {
        try {
            $request = $this->processRequest(
                $builder->toPsr7Request(),
                $builder
            );

            $response = $handler($request);

            return $this->processResponse($response, $builder);

        } catch (Throwable $e) {
            return $this->processError($e, $builder);
        }
    }

    // ==================== PIPELINE MANAGEMENT ====================

    public function count(): int
    {
        return count($this->middlewares);
    }

    public function isEmpty(): bool
    {
        return empty($this->middlewares);
    }

    public function clear(): self
    {
        $this->middlewares = [];
        $this->requestMiddlewares = [];
        $this->responseMiddlewares = [];
        $this->errorMiddlewares = [];

        return $this;
    }

    public function remove(callable $middleware): self
    {
        $this->middlewares = array_filter(
            $this->middlewares,
            fn ($m) => $m !== $middleware
        );

        return $this;
    }

    public function all(): array
    {
        return $this->middlewares;
    }

    // ==================== CONVENIENCE METHODS ====================

    public function with(callable|string $middleware): self
    {
        return $this->push($middleware);
    }

    public static function create(array $middlewares = []): self
    {
        return new static($middlewares);
    }

    public function clone(): self
    {
        return clone $this;
    }
}
