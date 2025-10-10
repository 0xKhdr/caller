# Laravel Caller Documentation

## Table of Contents
- [Installation](#installation)
- [Configuration](#configuration)
- [Basic Usage](#basic-usage)
- [Core Concepts](#core-concepts)
  - [Callers](#callers)
  - [Receivers](#receivers)
  - [DTOs](#dtos)
- [Advanced Usage](#advanced-usage)
- [Testing](#testing)
- [Examples](#examples)
- [API Reference](#api-reference)

## Installation

### Requirements
- PHP 8.1 or higher
- Laravel 9.0 or higher

### Service Provider (Auto-discovered)
The package will automatically register itself with Laravel.

## Configuration

Publish the configuration file (optional):
```bash
php artisan vendor:publish --provider="Raid\Caller\Providers\CallerServiceProvider" --tag="config"
```

### Configuration File
```php
return [
    /*
    |--------------------------------------------------------------------------
    | Default HTTP Options
    |--------------------------------------------------------------------------
    |
    | Default options that will be applied to every API call unless overridden
    | in specific Caller classes.
    |
    */
    'default_options' => [
        'timeout' => 30,
        'connect_timeout' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for API requests and responses.
    |
    */
    'logging' => [
        'enabled' => env('CALLER_LOGGING_ENABLED', false),
        'channel' => env('CALLER_LOGGING_CHANNEL', 'stack'),
        'level' => env('CALLER_LOGGING_LEVEL', 'debug'),
    ],
];
```

## Basic Usage

### 1. Create a DTO
```php
<?php

namespace App\Dtos;

use Raid\Caller\Dtos\DtoAbstract;

readonly class UserDto extends DtoAbstract
{
    public function __construct(
        protected int $id,
        protected string $name
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(id: $data['id'], name: $data['name']);
    }
}
```

### 2. Create a Receiver
```php
<?php

namespace App\Receivers\Users;

use Illuminate\Http\Client\Response;
use Raid\Caller\Receivers\ResponseReceiver;
use App\Dtos\UserDto;

readonly class FindUserReceiver extends ResponseReceiver
{
    public function __construct(
        protected int $status,
        protected UserDto $user
    ) {}

    public static function fromResponse(Response $response): static
    {
        return new static(
            status: $response->status(),
            user: UserDto::fromArray($response->json())
        );
    }

    protected function toSuccessResponse(): array
    {
        return [
            'message' => __('User found successfully'),
            'data' => $this->user->toArray(),
        ];
    }

    protected function toErrorResponse(): array
    {
        return [
            'message' => __('Failed to find user'),
        ];
    }
}
```

### 3. Create a Caller
```php
<?php

namespace App\Callers\Users;

use App\Receivers\Users\FindUserReceiver;
use Raid\Caller\Callers\GetCaller;

readonly class FindUserCaller extends GetCaller
{
    public function __construct(
        protected int $id
    ) {}

    public static function make(int $id): static
    {
        return new static(id: $id);
    }

    public function getUrl(): string
    {
        return "https://api.example.com/users/{$this->id}";
    }

    public function getOptions(): array
    {
        return [
            'headers' => ['Accept' => 'application/json'],
        ];
    }

    public function getReceiver(): string
    {
        return FindUserReceiver::class;
    }
}
```

### 4. Use the Caller
```php
<?php

namespace App\Http\Controllers;

use App\Callers\Users\FindUserCaller;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function find(int $userId): JsonResponse
    {
        $receiver = FindUserCaller::make($userId)->call();

        return response()->json(
            data: $receiver->toResponse(),
            status: $receiver->getStatus()
        );
    }
}
```

## Core Concepts

### Callers

Callers are responsible for defining API requests. They specify:
- HTTP method
- URL
- Request options
- Which Receiver to use

#### Caller Interface
```php
interface Caller
{
    public function call(): Receiver;
    public function getMethod(): string;
    public function getUrl(): string;
    public function getOptions(): array;
    public function getReceiver(): string;
}
```

#### Available Methods in CallerAbstract
- `call()`: Executes the API call
- `getMethod()`: Returns HTTP method
- `getUrl()`: Returns the API endpoint URL
- `getOptions()`: Returns request options
- `getReceiver()`: Returns the Receiver class name

### Receivers

Receivers handle the API response and transform it into DTOs. If you extend `ResponseReceiver`, you get `toResponse()` and `getStatus()` helpers via the `ToResponse` trait.

#### Receiver Interface
```php
interface Receiver
{
    public static function fromResponse(Response $response): static;
}
```

#### Available Helpers (when extending ResponseReceiver)
- `toResponse()`: Shape a success/error payload
- `getStatus()`: HTTP status integer

### DTOs

DTOs provide a structured, convenient way to access response data.

#### DTO Interface
```php
interface Dto
{
    public static function fromArray(array $data): static;
    public function has(string $key): bool;
    public function get(string $key, mixed $default = null): mixed;
}
```

#### Available Methods in DtoAbstract
- `has($key)`: Checks if a property exists
- `get($key, $default)`: Gets a property with optional default
- `toArray()`: Provided by `ToArray` trait

## Advanced Usage

### Custom HTTP Client
```php
class CustomCaller extends CallerAbstract
{
    public function getOptions(): array
    {
        return [
            'timeout' => 60,
            'retry' => [
                'times' => 3,
                'sleep' => 100,
            ],
        ];
    }
}
```

### Error Handling
Use the returned Receiver's `getStatus()` and `toResponse()` to decide success/failure when extending `ResponseReceiver`.

### Request/Response Logging
You can log around the caller execution at your application boundary as needed.

### HTTP Options
Anything supported by Laravel's `Http::send()` options may be provided via `getOptions()` (e.g., headers, query, json, timeout).

### Extension Points & Roadmap
- Error DTO standardization and exception mapping at boundaries.
- Rate limit aware retry middleware with backoff and header parsing.
- Optional circuit breaker (per-caller) with fallback Receiver.
- DTO scaffolding from OpenAPI to speed integration.

## Testing

### Mocking HTTP
Use Laravel HTTP fakes to drive your Receivers and endpoints in tests.

### Testing Custom Receivers
```php
class FindUserReceiverTest extends TestCase
{
    public function test_from_response()
    {
        $response = Http::response(['id' => 1, 'name' => 'John Doe'], 200);
        $receiver = FindUserReceiver::fromResponse($response);
        $this->assertSame(200, $receiver->getStatus());
        $this->assertSame('User found successfully', $receiver->toResponse()['message']);
    }
}
```

### Quick Receiver Unit Pattern
```php
class FindUserReceiverTest extends TestCase
{
    public function test_from_response_builds_dto_and_status()
    {
        $response = Http::response(['id' => 1, 'name' => 'John'], 200);
        $receiver = FindUserReceiver::fromResponse($response);
        $this->assertSame(200, $receiver->getStatus());
        $this->assertSame('User found successfully', $receiver->toResponse()['message']);
    }
}
```

## Examples

### POST Request with Payload
```php
readonly class StoreUserCaller extends PostCaller
{
    public function __construct(
        protected string $name,
        protected string $username,
        protected ?string $phone = null,
    ) {}

    public static function make(
        string $name,
        string $username,
        ?string $phone = null,
    ): static {
        return new static(
            name: $name,
            username: $username,
            phone: $phone
        );
    }
    
    public function getUrl(): string
    {
        return 'https://api.example.com/users';
    }

    public function getOptions(): array
    {
        return [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $this->toArray()
        ];
    }

    public function getReceiver(): string
    {
        return StoreUserReceiver::class;
    }
}
```

### Handling Paginated Responses
```php
readonly class PaginatedUsersReceiver extends ResponseReceiver
{
    public function __construct(
        protected int $status,
        protected array $users
    ) {}
    
    public static function fromResponse(Response $response): static
    {
        return new static(
            status: $response->status(),
            users: array_map(fn ($u) => UserDto::fromArray($u), $response->json()['users'] ?? [])
        );
    }
}
```

## API Reference

### CallerAbstract Methods
| Method          | Return Type | Description                   |
|-----------------|-------------|-------------------------------|
| `call()`        | `Receiver`  | Executes the API call         |
| `getMethod()`   | `string`    | HTTP method (GET, POST, etc.) |
| `getUrl()`      | `string`    | API endpoint URL              |
| `getOptions()`  | `array`     | Request options               |
| `getReceiver()` | `string`    | Receiver class name           |

### ResponseReceiver Helpers
| Method         | Return Type | Description                        |
|----------------|-------------|------------------------------------|
| `toResponse()` | `array`     | Success/error payload based on 2xx |
| `getStatus()`  | `int`       | HTTP status code                   |

### DtoAbstract Methods
| Method                | Return Type | Description               |
|-----------------------|-------------|---------------------------|
| `has($key)`           | `bool`      | Checks if property exists |
| `get($key, $default)` | `mixed`     | Gets property value       |
| `toArray()`           | `array`     | Converts to array         |

## Support

For issues and questions:
- Create an issue on [GitHub](https://github.com/0xKhdr/caller/issues)
- Check existing [discussions](https://github.com/0xKhdr/caller/discussions)

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.
