<?php

namespace Raid\Caller\Mappers\Implementations;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JsonException;
use JsonSerializable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Raid\Caller\Builders\Contracts\RequestBuilder as RequestBuilderContract;
use Raid\Caller\Mappers\Contracts\ResponseMapper as ResponseMapperContract;
use Raid\Caller\Mappers\MapperAbstract;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Throwable;

class ResponseMapper extends MapperAbstract implements Arrayable, Jsonable, JsonSerializable, ResponseMapperContract
{
    protected ?ResponseInterface $response;

    protected ?RequestBuilderContract $builder;

    protected ?Throwable $exception;

    protected mixed $cachedData = null;

    protected array $transformers = [];

    protected array $metadata = [];

    public function __construct(
        ?ResponseInterface $response = null,
        ?RequestBuilderContract $builder = null,
        ?Throwable $exception = null
    ) {
        $this->response = $response;
        $this->builder = $builder;
        $this->exception = $exception;
        $this->metadata = [
            'execution_time' => microtime(true),
            'attempts' => 1,
        ];
    }

    // ==================== STATUS & VALIDATION ====================

    public function status(): int
    {
        return $this->response?->getStatusCode() ?? 0;
    }

    public function successful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    public function failed(): bool
    {
        return ! $this->successful();
    }

    public function clientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    public function serverError(): bool
    {
        return $this->status() >= 500;
    }

    public function ok(): bool
    {
        return $this->status() === 200;
    }

    public function created(): bool
    {
        return $this->status() === 201;
    }

    public function accepted(): bool
    {
        return $this->status() === 202;
    }

    public function noContent(): bool
    {
        return $this->status() === 204;
    }

    public function unauthorized(): bool
    {
        return $this->status() === 401;
    }

    public function forbidden(): bool
    {
        return $this->status() === 403;
    }

    public function notFound(): bool
    {
        return $this->status() === 404;
    }

    public function conflict(): bool
    {
        return $this->status() === 409;
    }

    public function unprocessable(): bool
    {
        return $this->status() === 422;
    }

    public function tooManyRequests(): bool
    {
        return $this->status() === 429;
    }

    // ==================== HEADERS & COOKIES ====================

    public function headers(): array
    {
        if (! $this->response) {
            return [];
        }

        return $this->response->getHeaders();
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $headers = $this->headers();

        if (! isset($headers[$key])) {
            return $default;
        }

        return count($headers[$key]) === 1 ? $headers[$key][0] : $headers[$key];
    }

    public function contentType(): ?string
    {
        return $this->header('Content-Type');
    }

    public function isJson(): bool
    {
        return str_contains($this->contentType() ?? '', 'application/json');
    }

    public function isXml(): bool
    {
        return str_contains($this->contentType() ?? '', 'application/xml') ||
            str_contains($this->contentType() ?? '', 'text/xml');
    }

    public function isHtml(): bool
    {
        return str_contains($this->contentType() ?? '', 'text/html');
    }

    public function isPlain(): bool
    {
        return str_contains($this->contentType() ?? '', 'text/plain');
    }

    // ==================== RESPONSE BODY PROCESSING ====================

    public function body(): string
    {
        if (! $this->response) {
            return '';
        }

        return (string) $this->response->getBody();
    }

    public function stream(): ?StreamInterface
    {
        return $this->response?->getBody();
    }

    /**
     * @throws JsonException
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        if (! $this->isJson() || empty($this->body())) {
            return $default;
        }

        if ($this->cachedData === null) {
            $this->cachedData = json_decode($this->body(), true, 512, JSON_THROW_ON_ERROR);
        }

        if ($key === null) {
            return $this->cachedData;
        }

        return data_get($this->cachedData, $key, $default);
    }

    /**
     * @throws JsonException
     */
    public function array(?string $key = null, mixed $default = null): array
    {
        $data = $this->json($key, $default);

        if (is_array($data)) {
            return $data;
        }

        return $data ? (array) $data : [];
    }

    /**
     * @throws JsonException
     */
    public function object(): ?object
    {
        $data = $this->json();

        return $data ? (object) $data : null;
    }

    /**
     * @throws JsonException
     */
    public function collect(): Collection
    {
        return Collection::make($this->json());
    }

    // ==================== DTO MAPPING ====================

    /**
     * @throws JsonException
     */
    public function toDto(string $dtoClass, ?string $dataPath = null): mixed
    {
        if (! class_exists($dtoClass)) {
            throw new InvalidArgumentException("DTO class {$dtoClass} does not exist");
        }

        $data = $dataPath ? $this->json($dataPath) : $this->json();

        // Handle DTO creation based on common patterns
        if (method_exists($dtoClass, 'fromArray')) {
            return $dtoClass::fromArray($data ?? []);
        }

        if (method_exists($dtoClass, 'fromResponse')) {
            return $dtoClass::fromResponse($this);
        }

        // Reflection-based creation for simple DTOs
        try {
            $reflection = new ReflectionClass($dtoClass);
            $constructor = $reflection->getConstructor();

            if (! $constructor || $constructor->getNumberOfParameters() === 0) {
                $instance = $reflection->newInstance();

                // Hydrate properties if they exist
                if (is_array($data)) {
                    foreach ($data as $key => $value) {
                        if ($reflection->hasProperty($key)) {
                            $property = $reflection->getProperty($key);
                            $property->setAccessible(true);
                            $property->setValue($instance, $value);
                        }
                    }
                }

                return $instance;
            }

            // Create with constructor parameters
            return $reflection->newInstance($data);

        } catch (ReflectionException $e) {
            throw new RuntimeException("Failed to create DTO instance: {$e->getMessage()}");
        }
    }

    /**
     * @throws JsonException
     */
    public function toDtoCollection(string $dtoClass, ?string $dataPath = null): Collection
    {
        $data = $dataPath ? $this->json($dataPath) : $this->json();

        if (! is_array($data)) {
            return Collection::make();
        }

        // Handle paginated responses
        if (isset($data['data']) && is_array($data['data'])) {
            $items = $data['data'];
        } else {
            $items = $data;
        }

        return Collection::make($items)->map(function ($item) use ($dtoClass) {
            return $this->createDtoFromData($dtoClass, $item);
        });
    }

    // ==================== TRANSFORMERS & MUTATORS ====================

    public function transform(callable $transformer): self
    {
        $this->transformers[] = $transformer;

        return $this;
    }

    /**
     * @throws JsonException
     */
    public function map(callable $mapper): mixed
    {
        $data = $this->json();

        foreach ($this->transformers as $transformer) {
            $data = $transformer($data);
        }

        return $mapper($data);
    }

    public function withMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);

        return $this;
    }

    public function getMetadata(): array
    {
        return array_merge($this->metadata, [
            'status' => $this->status(),
            'successful' => $this->successful(),
            'content_type' => $this->contentType(),
            'content_length' => $this->header('Content-Length'),
        ]);
    }

    // ==================== ERROR HANDLING ====================

    /**
     * @throws Throwable
     */
    public function throw(): self
    {
        if ($this->exception) {
            throw $this->exception;
        }

        if ($this->failed()) {
            throw new RuntimeException(
                "HTTP request failed with status code: {$this->status()}",
                $this->status()
            );
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function throwIf(callable $condition): self
    {
        if ($condition($this)) {
            $this->throw();
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function throwIfStatus(int $status): self
    {
        return $this->throwIf(fn ($response) => $response->status() === $status);
    }

    /**
     * @throws Throwable
     */
    public function throwIfClientError(): self
    {
        return $this->throwIf(fn ($response) => $response->clientError());
    }

    /**
     * @throws Throwable
     */
    public function throwIfServerError(): self
    {
        return $this->throwIf(fn ($response) => $response->serverError());
    }

    public function onSuccess(callable $callback): self
    {
        if ($this->successful()) {
            $callback($this);
        }

        return $this;
    }

    public function onError(callable $callback): self
    {
        if ($this->failed() || $this->exception) {
            $callback($this, $this->exception);
        }

        return $this;
    }

    public function tap(callable $callback): self
    {
        $callback($this);

        return $this;
    }

    // ==================== LAZY EVALUATION ====================

    public function when(bool $condition, callable $callback): self
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    public function unless(bool $condition, callable $callback): self
    {
        if (! $condition) {
            $callback($this);
        }

        return $this;
    }

    // ==================== OUTPUT FORMATS ====================

    /**
     * @throws JsonException
     */
    public function toArray(): array
    {
        return $this->array();
    }

    /**
     * @throws JsonException
     */
    public function toJson($options = 0): string
    {
        if ($this->isJson()) {
            return $this->body();
        }

        return json_encode($this->toArray(), $options);
    }

    /**
     * @throws JsonException
     */
    public function toObject(): object
    {
        return $this->object() ?? (object) [];
    }

    /**
     * @throws JsonException
     */
    public function toCollect(): Collection
    {
        return $this->collect();
    }

    public function toString(): string
    {
        return $this->body();
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    // ==================== DEBUGGING ====================

    public function dump(): self
    {
        dump([
            'status' => $this->status(),
            'headers' => $this->headers(),
            'body' => $this->body(),
            'json' => $this->json(),
            'metadata' => $this->getMetadata(),
        ]);

        return $this;
    }

    public function dd(): never
    {
        dd([
            'status' => $this->status(),
            'headers' => $this->headers(),
            'body' => $this->body(),
            'json' => $this->json(),
            'metadata' => $this->getMetadata(),
            'builder' => $this->builder?->toArray(),
        ]);
    }

    // ==================== INTERFACE IMPLEMENTATIONS ====================

    /**
     * @throws JsonException
     */
    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status(),
            'headers' => $this->headers(),
            'data' => $this->json(),
            'metadata' => $this->getMetadata(),
        ];
    }

    // ==================== PRIVATE HELPERS ====================

    private function createDtoFromData(string $dtoClass, array $data): mixed
    {
        if (method_exists($dtoClass, 'fromArray')) {
            return $dtoClass::fromArray($data);
        }

        return new $dtoClass(...$data);
    }
}
