<?php

namespace Raid\Caller\Executors\Contracts;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Raid\Caller\Builders\Contracts\RequestBuilder;

interface RequestExecutor extends Executor
{
    public function call(RequestBuilder $builder): ResponseInterface;

    public function callAsync(RequestBuilder $builder): PromiseInterface;

    public function withRequestMiddleware(callable $middleware): self;

    public function withResponseMiddleware(callable $middleware): self;

    public function sendRequest(RequestInterface $request): ResponseInterface;
}
