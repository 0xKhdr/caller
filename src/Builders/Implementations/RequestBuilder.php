<?php

namespace Raid\Caller\Builders\Implementations;

use BadMethodCallException;
use Closure;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\UploadedFile;
use JsonException;
use JsonSerializable;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Raid\Caller\Builders\BuilderAbstract;
use Raid\Caller\Builders\Contracts\RequestBuilder as RequestBuilderContract;
use Raid\Caller\Executors\Contracts\RequestExecutor as RequestExecutorContract;
use Raid\Caller\Mappers\Contracts\ResponseMapper as ResponseMapperContract;
use Raid\Caller\Mappers\Implementations\ResponseMapper;

class RequestBuilder extends BuilderAbstract implements Arrayable, JsonSerializable, RequestBuilderContract
{
    protected string $baseUrl = '';

    protected string $method = '';

    protected string $url = '';

    protected array $headers = [];

    protected array $query = [];

    protected mixed $body = null;

    protected array $options = [];

    protected array $middleware = [];

    protected array $macros = [];

    protected bool $throwExceptions = false;

    protected array $throwConditions = [];

    protected int $retryTimes = 0;

    protected int $retrySleep = 0;

    protected ?Closure $retryWhen = null;

    protected int $timeout = 30;

    protected bool $async = false;

    protected bool $debug = false;

    public static function to(string $url): self
    {
        return (new static)->withUrl($url);
    }

    // ==================== HTTP METHODS ====================

    public function get(): self
    {
        return $this->withMethod('GET');
    }

    public function post(mixed $body = null): self
    {
        $caller = $this->withMethod('POST');

        if ($body !== null) {
            return $caller->withBody($body);
        }

        return $caller;
    }

    public function put(mixed $body = null): self
    {
        $caller = $this->withMethod('PUT');

        if ($body !== null) {
            return $caller->withBody($body);
        }

        return $caller;
    }

    public function patch(mixed $body = null): self
    {
        $caller = $this->withMethod('PATCH');

        if ($body !== null) {
            return $caller->withBody($body);
        }

        return $caller;
    }

    public function delete(mixed $body = null): self
    {
        $caller = $this->withMethod('DELETE');

        if ($body !== null) {
            return $caller->withBody($body);
        }

        return $caller;
    }

    public function head(): self
    {
        return $this->withMethod('HEAD');
    }

    public function options(): self
    {
        return $this->withMethod('OPTIONS');
    }

    // ==================== URL & CONFIGURATION ====================

    public function withUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function withBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        return $this;
    }

    public function withMethod(string $method): self
    {
        $this->method = strtoupper($method);

        return $this;
    }

    // ==================== HEADERS ====================

    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function withHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

    public function accept(string $contentType): self
    {
        return $this->withHeader('Accept', $contentType);
    }

    public function acceptJson(): self
    {
        return $this->accept('application/json');
    }

    public function contentType(string $contentType): self
    {
        return $this->withHeader('Content-Type', $contentType);
    }

    public function asJson(): self
    {
        return $this->contentType('application/json')->acceptJson();
    }

    public function asForm(): self
    {
        return $this->contentType('application/x-www-form-urlencoded');
    }

    public function asMultipart(): self
    {
        return $this->contentType('multipart/form-data');
    }

    // ==================== AUTHENTICATION ====================

    public function withBasicAuth(string $username, string $password): self
    {
        return $this->withHeader(
            'Authorization',
            'Basic '.base64_encode("{$username}:{$password}")
        );
    }

    public function withToken(string $token, string $type = 'Bearer'): self
    {
        return $this->withHeader('Authorization', "{$type} {$token}");
    }

    public function withDigestAuth(string $username, string $password): self
    {
        $this->options['auth'] = [$username, $password, 'digest'];

        return $this;
    }

    // ==================== BODY & CONTENT ====================

    public function withBody(mixed $body, string $contentType = 'application/json'): self
    {
        $this->body = $body;

        return $this->contentType($contentType);
    }

    public function withJson(array $data): self
    {
        return $this->withBody($data)->asJson();
    }

    public function withFormParams(array $data): self
    {
        $this->body = $data;

        return $this->asForm();
    }

    public function withMultipart(array $data): self
    {
        $this->body = $data;

        return $this->asMultipart();
    }

    public function withQuery(array $query): self
    {
        $this->query = array_merge($this->query, $query);

        return $this;
    }

    public function withQueryParam(string $key, mixed $value): self
    {
        $this->query[$key] = $value;

        return $this;
    }

    // ==================== OPTIONS & BEHAVIOR ====================

    public function withOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function withOption(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this->withOption('timeout', $seconds);
    }

    public function connectTimeout(int $seconds): self
    {
        return $this->withOption('connect_timeout', $seconds);
    }

    public function retry(int $times, int $sleep = 0, ?callable $when = null): self
    {
        $this->retryTimes = $times;
        $this->retrySleep = $sleep;
        $this->retryWhen = $when;

        return $this;
    }

    public function withoutRedirecting(): self
    {
        return $this->withOption('allow_redirects', false);
    }

    public function withoutVerifying(): self
    {
        return $this->withOption('verify', false);
    }

    public function withCookies(array $cookies): self
    {
        return $this->withOption('cookies', $cookies);
    }

    public function withProxy(string $proxy): self
    {
        return $this->withOption('proxy', $proxy);
    }

    // ==================== MIDDLEWARE ====================

    public function withMiddleware(array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    public function withRequestMiddleware(callable $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    // ==================== ERROR HANDLING ====================

    public function throw(): self
    {
        $this->throwExceptions = true;

        return $this;
    }

    public function throwIf(callable|bool $condition): self
    {
        $this->throwConditions[] = $condition;

        return $this;
    }

    public function throwIfStatus(callable|int $condition): self
    {
        if (is_int($condition)) {
            $condition = fn ($status) => $status === $condition;
        }

        return $this->throwIf(fn ($response) => $condition($response->status()));
    }

    public function throwIfClientError(): self
    {
        return $this->throwIfStatus(fn ($status) => $status >= 400 && $status < 500);
    }

    public function throwIfServerError(): self
    {
        return $this->throwIfStatus(fn ($status) => $status >= 500);
    }

    // ==================== DEBUGGING ====================

    public function dump(): self
    {
        $this->debug = true;

        return $this->withOption('debug', true);
    }

    public function dd(): self
    {
        $this->dump();

        // This will be handled in the executor
        return $this->withOption('dd', true);
    }

    // ==================== ASYNC SUPPORT ====================

    public function async(): self
    {
        $this->async = true;

        return $this;
    }

    // ==================== EXECUTION ====================

    /**
     * @throws Exception
     */
    public function call(): ResponseMapperContract
    {
        /** @var RequestExecutorContract $executor */
        $executor = app(RequestExecutorContract::class);

        try {
            if ($this->async) {
                $promise = $executor->executeAsync($this);

                return new ResponseMapper($promise->wait(), $this);
            }

            $response = $executor->execute($this);

            return new ResponseMapper($response, $this);

        } catch (Exception $e) {
            if ($this->throwExceptions) {
                throw $e;
            }

            return new ResponseMapper(null, $this, $e);
        }
    }

    public function callAsync(): PromiseInterface
    {
        $executor = app(RequestExecutorContract::class);

        return $executor->executeAsync($this);
    }

    // ==================== MACROS ====================

    public function macro(string $name, callable $macro): void
    {
        $this->macros[$name] = $macro;
    }

    public function __call(string $method, array $parameters)
    {
        if (isset($this->macros[$method])) {
            return $this->macros[$method](...$parameters);
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }

    // ==================== GETTERS ====================

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): string
    {
        if ($this->baseUrl && ! str_starts_with($this->url, 'http')) {
            return $this->baseUrl.'/'.ltrim($this->url, '/');
        }

        return $this->url;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }

    public function getOptions(): array
    {
        return array_merge([
            'timeout' => $this->timeout,
            'headers' => $this->headers,
            'query' => $this->query,
        ], $this->options);
    }

    public function getCookies(): array
    {
        return $this->options['cookies'] ?? [];
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function isJson(): bool
    {
        return ($this->headers['Content-Type'] ?? '') === 'application/json';
    }

    public function hasFiles(): bool
    {
        return is_array($this->body) && collect($this->body)->contains(fn ($item) => $item instanceof UploadedFile);
    }

    public function getFiles(): array
    {
        if (! $this->hasFiles()) {
            return [];
        }

        return collect($this->body)
            ->map(fn ($value, $key) => [
                'name' => $key,
                'contents' => $value instanceof UploadedFile
                    ? $value->get()
                    : $value,
                'filename' => $value instanceof UploadedFile
                    ? $value->getClientOriginalName()
                    : null,
            ])
            ->values()
            ->toArray();
    }

    // ==================== INTERFACE IMPLEMENTATIONS ====================

    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'url' => $this->getUrl(),
            'headers' => $this->headers,
            'query' => $this->query,
            'body' => $this->body,
            'options' => $this->options,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * @throws JsonException
     */
    public function toPsr7Request(): RequestInterface
    {
        $method = $this->getMethod();
        $uri = $this->buildPsr7Uri();
        $headers = $this->buildPsr7Headers();
        $body = $this->buildPsr7Body();

        // Create the PSR-7 request
        $request = new Request($method, $uri, $headers, $body);

        // Apply any additional PSR-7 modifications
        return $this->applyPsr7Modifications($request);
    }

    private function buildPsr7Uri(): string
    {
        $uri = $this->getUrl();

        // Add query parameters if present
        if (! empty($this->query)) {
            $separator = str_contains($uri, '?') ? '&' : '?';
            $uri .= $separator.http_build_query($this->query, '', '&', PHP_QUERY_RFC3986);
        }

        return $uri;
    }

    /**
     * @throws JsonException
     */
    private function buildPsr7Headers(): array
    {
        $headers = [];

        foreach ($this->headers as $name => $value) {
            // Normalize header names (Content-Type -> Content-Type)
            $normalizedName = $this->normalizeHeaderName($name);

            // Handle array values (convert to string)
            if (is_array($value)) {
                $headers[$normalizedName] = $value;
            } else {
                $headers[$normalizedName] = [$value];
            }
        }

        // Ensure Content-Length is set for requests with body
        if ($this->hasBody() && ! isset($headers['Content-Length'])) {
            $body = $this->buildPsr7Body();
            if ($body->getSize() > 0) {
                $headers['Content-Length'] = [$body->getSize()];
            }
        }

        return $headers;
    }

    /**
     * @throws JsonException
     */
    private function buildPsr7Body(): StreamInterface
    {
        // Empty body for methods that don't typically have one
        if (in_array($this->method, ['GET', 'HEAD', 'OPTIONS']) && empty($this->body)) {
            return Utils::streamFor('');
        }

        // Handle multipart form data (file uploads)
        if ($this->isMultipart()) {
            return $this->buildMultipartBody();
        }

        // Handle form URL encoded data
        if ($this->isFormUrlEncoded()) {
            return Utils::streamFor(http_build_query($this->body, '', '&'));
        }

        // Handle JSON data
        if ($this->isJson() && ! empty($this->body)) {
            return Utils::streamFor(json_encode($this->body, JSON_THROW_ON_ERROR));
        }

        // Handle raw string body
        if (is_string($this->body)) {
            return Utils::streamFor($this->body);
        }

        // Handle stream resources
        if (is_resource($this->body)) {
            return Utils::streamFor($this->body);
        }

        // Handle Psr\Http\Message\StreamInterface
        if ($this->body instanceof StreamInterface) {
            return $this->body;
        }

        // Default empty body
        return Utils::streamFor('');
    }

    private function buildMultipartBody(): MultipartStream
    {
        $elements = [];

        foreach ($this->body as $name => $value) {
            $element = [
                'name' => $name,
            ];

            // Handle file uploads
            if ($value instanceof UploadedFile) {
                $element['contents'] = $value->get();
                $element['filename'] = $value->getClientOriginalName();
                $element['headers'] = [
                    'Content-Type' => $value->getClientMimeType(),
                ];
            }
            // Handle Psr\Http\Message\StreamInterface
            elseif ($value instanceof StreamInterface) {
                $element['contents'] = $value;
            }
            // Handle resources
            elseif (is_resource($value)) {
                $element['contents'] = Utils::streamFor($value);
            }
            // Handle arrays (convert to JSON)
            elseif (is_array($value)) {
                $element['contents'] = json_encode($value, JSON_THROW_ON_ERROR);
                $element['headers'] = [
                    'Content-Type' => 'application/json',
                ];
            }
            // Handle simple values
            else {
                $element['contents'] = (string) $value;
            }

            $elements[] = $element;
        }

        return new MultipartStream($elements);
    }

    /**
     * Apply any final modifications to the PSR-7 request
     */
    private function applyPsr7Modifications(RequestInterface $request): RequestInterface
    {
        // Apply protocol version if specified in options
        if (isset($this->options['version'])) {
            $request = $request->withProtocolVersion($this->options['version']);
        }

        // Apply any custom modifications via options
        if (isset($this->options['psr7_modifiers']) && is_array($this->options['psr7_modifiers'])) {
            foreach ($this->options['psr7_modifiers'] as $modifier) {
                if (is_callable($modifier)) {
                    $request = $modifier($request, $this);
                }
            }
        }

        return $request;
    }

    private function normalizeHeaderName(string $name): string
    {
        return implode('-', array_map('ucfirst', explode('-', strtolower($name))));
    }

    private function hasBody(): bool
    {
        return ! empty($this->body) || in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE']);
    }

    private function isMultipart(): bool
    {
        return ($this->headers['Content-Type'] ?? '') === 'multipart/form-data' &&
            ! empty($this->body) &&
            is_array($this->body);
    }

    private function isFormUrlEncoded(): bool
    {
        return ($this->headers['Content-Type'] ?? '') === 'application/x-www-form-urlencoded' &&
            ! empty($this->body) &&
            is_array($this->body);
    }

    public function getBodyForDebug(): mixed
    {
        if ($this->isMultipart()) {
            $debugBody = [];
            foreach ($this->body as $key => $value) {
                if ($value instanceof UploadedFile) {
                    $debugBody[$key] = [
                        'type' => 'file',
                        'name' => $value->getClientOriginalName(),
                        'size' => $value->getSize(),
                        'mime_type' => $value->getClientMimeType(),
                    ];
                } else {
                    $debugBody[$key] = $value;
                }
            }

            return $debugBody;
        }

        return $this->body;
    }
}
