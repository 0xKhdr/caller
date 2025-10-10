```markdown
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

### Via Composer
```bash
composer require 0xkhdr/caller
```

### Service Provider (Auto-discovered)
The package will automatically register itself with Laravel.

## Configuration

Publish the configuration file (optional):
```bash
php artisan vendor:publish --provider="Khdr\Caller\CallerServiceProvider" --tag="config"
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

use Khdr\Caller\Dtos\DtoAbstract;

class UserDto extends DtoAbstract
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            id: $data['id'],
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null
        );
    }
}
```

### 2. Create a Receiver
```php
<?php

namespace App\Receivers;

use Illuminate\Http\Client\Response;
use Khdr\Caller\Receivers\ReceiverAbstract;
use App\Dtos\UserDto;

class UserReceiver extends ReceiverAbstract
{
    public static function fromResponse(Response $response): static
    {
        $data = $response->json();

        return new static(
            UserDto::fromArray($data['user']),
            $response->status(),
            $response->headers()
        );
    }
}
```

### 3. Create a Caller
```php
<?php

namespace App\Callers;

use Khdr\Caller\Callers\CallerAbstract;
use App\Receivers\UserReceiver;

class GetUserCaller extends CallerAbstract
{
    protected int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUrl(): string
    {
        return "https://api.example.com/users/{$this->userId}";
    }

    public function getOptions(): array
    {
        return [
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.example.api_key'),
            ],
        ];
    }

    public function getReceiver(): string
    {
        return UserReceiver::class;
    }
}
```

### 4. Use the Caller
```php
<?php

namespace App\Http\Controllers;

use App\Callers\GetUserCaller;

class UserController extends Controller
{
    public function show(int $userId)
    {
        $caller = new GetUserCaller($userId);
        $response = $caller->call();

        // Access the DTO
        $user = $response->getDto();

        return view('users.show', [
            'user' => $user
        ]);
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

Receivers handle the API response and transform it into DTOs.

#### Receiver Interface
```php
interface Receiver
{
    public static function fromResponse(Response $response): static;
    public function getDto(): ?Dto;
    public function getStatusCode(): int;
    public function getHeaders(): array;
}
```

#### Available Methods in ReceiverAbstract
- `getDto()`: Returns the DTO instance
- `getStatusCode()`: Returns HTTP status code
- `getHeaders()`: Returns response headers
- `isSuccessful()`: Checks if request was successful (200-299)

### DTOs

DTOs provide a structured, type-safe way to access response data.

#### DTO Interface
```php
interface Dto
{
    public static function fromArray(array $data): static;
    public function has(string $key): bool;
    public function get(string $key, mixed $default = null): mixed;
    public function toArray(): array;
}
```

#### Available Methods in DtoAbstract
- `has($key)`: Checks if a property exists
- `get($key, $default)`: Gets a property with optional default
- `toArray()`: Converts DTO to array
- `toJson()`: Converts DTO to JSON

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
```php
try {
    $caller = new GetUserCaller(123);
    $response = $caller->call();
    
    if (!$response->isSuccessful()) {
        throw new ApiException('API request failed');
    }
    
    $user = $response->getDto();
} catch (Exception $e) {
    // Handle exception
    Log::error('API call failed: ' . $e->getMessage());
}
```

### Request/Response Logging
The package automatically logs requests and responses when logging is enabled in the configuration.

### Middleware Support
```php
class GetUserCaller extends CallerAbstract
{
    public function getOptions(): array
    {
        return [
            'middleware' => [
                new RetryMiddleware(3, 100),
            ],
        ];
    }
}
```

## Testing

### Mocking Callers
```php
use Khdr\Caller\Testing\CallerFake;

class UserControllerTest extends TestCase
{
    public function test_get_user()
    {
        // Fake the caller
        CallerFake::fake([
            GetUserCaller::class => [
                'dto' => new UserDto(1, 'John Doe', 'john@example.com'),
                'status' => 200,
            ],
        ]);

        $response = $this->get('/users/1');

        $response->assertStatus(200);
        CallerFake::assertCalled(GetUserCaller::class);
    }
}
```

### Testing Custom Receivers
```php
class UserReceiverTest extends TestCase
{
    public function test_from_response()
    {
        $response = new Response('{
            "user": {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com"
            }
        }', 200);

        $receiver = UserReceiver::fromResponse($response);
        $dto = $receiver->getDto();

        $this->assertEquals(1, $dto->id);
        $this->assertEquals('John Doe', $dto->name);
    }
}
```

## Examples

### POST Request with Payload
```php
class CreateUserCaller extends CallerAbstract
{
    public function __construct(
        protected array $userData
    ) {}

    public function getMethod(): string
    {
        return 'POST';
    }

    public function getUrl(): string
    {
        return 'https://api.example.com/users';
    }

    public function getOptions(): array
    {
        return [
            'json' => $this->userData,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];
    }

    public function getReceiver(): string
    {
        return UserReceiver::class;
    }
}
```

### Handling Paginated Responses
```php
class PaginatedUserReceiver extends ReceiverAbstract
{
    public static function fromResponse(Response $response): static
    {
        $data = $response->json();
        
        $users = array_map(function ($userData) {
            return UserDto::fromArray($userData);
        }, $data['users']);

        return new static(
            new UserCollectionDto($users, $data['meta']),
            $response->status(),
            $response->headers()
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

### ReceiverAbstract Methods
| Method            | Return Type | Description              |
|-------------------|-------------|--------------------------|
| `getDto()`        | `?Dto`      | Returns the DTO instance |
| `getStatusCode()` | `int`       | HTTP status code         |
| `getHeaders()`    | `array`     | Response headers         |
| `isSuccessful()`  | `bool`      | Checks if status is 2xx  |

### DtoAbstract Methods
| Method                | Return Type | Description               |
|-----------------------|-------------|---------------------------|
| `has($key)`           | `bool`      | Checks if property exists |
| `get($key, $default)` | `mixed`     | Gets property value       |
| `toArray()`           | `array`     | Converts to array         |
| `toJson()`            | `string`    | Converts to JSON          |

## Support

For issues and questions:
- Create an issue on [GitHub](https://github.com/0xKhdr/caller/issues)
- Check existing [discussions](https://github.com/0xKhdr/caller/discussions)

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.
```
