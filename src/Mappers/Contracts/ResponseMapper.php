<?php

namespace Raid\Caller\Mappers\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use JsonException;
use JsonSerializable;
use Psr\Http\Message\StreamInterface;
use Throwable;

interface ResponseMapper extends Arrayable, Jsonable, JsonSerializable, Mapper
{
    public function status(): int;

    public function successful(): bool;

    public function failed(): bool;

    public function clientError(): bool;

    public function serverError(): bool;

    public function ok(): bool;

    public function created(): bool;

    public function accepted(): bool;

    public function noContent(): bool;

    public function unauthorized(): bool;

    public function forbidden(): bool;

    public function notFound(): bool;

    public function conflict(): bool;

    public function unprocessable(): bool;

    public function tooManyRequests(): bool;

    public function headers(): array;

    public function header(string $key, mixed $default = null): mixed;

    public function contentType(): ?string;

    public function isJson(): bool;

    public function isXml(): bool;

    public function isHtml(): bool;

    public function isPlain(): bool;

    public function body(): string;

    public function stream(): ?StreamInterface;

    /**
     * @throws JsonException
     */
    public function json(?string $key = null, mixed $default = null): mixed;

    /**
     * @throws JsonException
     */
    public function array(?string $key = null, mixed $default = null): array;

    /**
     * @throws JsonException
     */
    public function object(): ?object;

    /**
     * @throws JsonException
     */
    public function collect(): Collection;

    /**
     * @throws JsonException
     */
    public function toDto(string $dtoClass, ?string $dataPath = null): mixed;

    /**
     * @throws JsonException
     */
    public function toDtoCollection(string $dtoClass, ?string $dataPath = null): Collection;

    public function transform(callable $transformer): self;

    /**
     * @throws JsonException
     */
    public function map(callable $mapper): mixed;

    public function withMetadata(array $metadata): self;

    public function getMetadata(): array;

    /**
     * @throws Throwable
     */
    public function throw(): self;

    /**
     * @throws Throwable
     */
    public function throwIf(callable $condition): self;

    /**
     * @throws Throwable
     */
    public function throwIfStatus(int $status): self;

    /**
     * @throws Throwable
     */
    public function throwIfClientError(): self;

    /**
     * @throws Throwable
     */
    public function throwIfServerError(): self;

    public function onSuccess(callable $callback): self;

    public function onError(callable $callback): self;

    public function tap(callable $callback): self;

    public function when(bool $condition, callable $callback): self;

    public function unless(bool $condition, callable $callback): self;

    /**
     * @throws JsonException
     */
    public function toArray(): array;

    /**
     * @throws JsonException
     */
    public function toJson($options = 0): string;

    /**
     * @throws JsonException
     */
    public function toObject(): object;

    /**
     * @throws JsonException
     */
    public function toCollect(): Collection;

    public function toString(): string;

    public function __toString(): string;

    public function dump(): self;

    public function dd(): never;

    /**
     * @throws JsonException
     */
    public function jsonSerialize(): array;
}
