<?php

namespace Raid\Caller\Builders\Contracts;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Raid\Caller\Mappers\Contracts\ResponseMapper;

interface RequestBuilder extends Builder
{
    public function get(): self;

    public function post(mixed $body = null): self;

    public function put(mixed $body = null): self;

    public function patch(mixed $body = null): self;

    public function delete(mixed $body = null): self;

    public function withUrl(string $url): self;

    public function withHeaders(array $headers): self;

    public function withBody(mixed $body, string $contentType = 'application/json'): self;

    public function withOptions(array $options): self;

    public function call(): ResponseMapper;

    public function callAsync(): PromiseInterface;

    public function getMethod(): string;

    public function getUrl(): string;

    public function getHeaders(): array;

    public function getBody(): mixed;

    public function getOptions(): array;

    public function getCookies(): array;

    public function toPsr7Request(): RequestInterface;
}
