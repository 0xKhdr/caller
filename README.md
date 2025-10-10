# Raid Caller Package

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/laravel-11%2B-orange.svg)](https://laravel.com/)

A modular, testable, and maintainable package for encapsulating outbound HTTP calls in Laravel. Raid Caller enforces a clean separation of concerns for HTTP requests, making your codebase more robust and easier to extend.

## Features
- **Separation of concerns:** Caller (intent), Service (execution), Receiver (parsing), DTO (domain model)
- **Immutable DTOs:** Safe, readonly data transfer objects
- **Extensible:** Easy to add new endpoints and customize behavior
- **Testable:** Works seamlessly with Laravel HTTP fakes

## Installation

```bash
composer require raid/caller
```

- Compatible with Laravel 11+
- The service provider `Raid\\Caller\\Providers\\CallerServiceProvider` is auto-discovered. If not, register it manually.
- To publish the config (if available):

```bash
php artisan vendor:publish --tag=caller
```

## Quick Start

Here's a minimal example for making a GET request:

```php
readonly class GetUsersCaller extends \Raid\Caller\Callers\GetCaller {
    public function getUrl(): string { return 'https://api.example.com/users'; }
    public function getReceiver(): string { return GetUsersReceiver::class; }
}

readonly class GetUsersReceiver extends \Raid\Caller\Receivers\ResponseReceiver {
    public function __construct(protected int $status, protected array $users) {}
    public static function fromResponse(\Illuminate\Http\Client\Response $r): static {
        return new static(status: $r->status(), users: array_map(fn(array $u) => UserDto::fromArray($u), $r->json()));
    }
    public function toSuccessResponse(): array { return ['message' => 'Users fetched', 'data' => array_map(fn(UserDto $u) => $u->toArray(), $this->users)]; }
    public function toErrorResponse(): array { return ['message' => 'Failed to fetch users']; }
}
```

## Concepts

- **Callers:** Define HTTP method, URL, options, and receiver. ([Docs](./docs/02-callers.md))
- **Receivers:** Parse responses and shape output. ([Docs](./docs/03-receivers.md))
- **DTOs:** Immutable data models. ([Docs](./docs/04-dtos.md))
- **Service:** Orchestrates execution and extension points. ([Docs](./docs/05-services.md))
- **Traits & Provider:** Utilities and configuration. ([Docs](./docs/06-traits.md), [Docs](./docs/07-providers.md))

See the [Overview](./docs/00-overview.md) for a high-level map.

## Lifecycle

See [Lifecycle](./docs/01-lifecycle.md) for a summary and sequence diagram.

## Advanced Usage
- Customize options, headers, and caching in Callers
- Extend Receivers for custom response handling
- Add new DTOs for your domain models

## Testing

Use Laravel HTTP fakes to test Callers and Receivers in isolation. See [Testing](./docs/08-testing.md).

## Observability & Conventions

- Logging, correlation, and metrics are supported. See [Observability](./docs/09-observability.md).
- Coding norms and best practices: [Conventions](./docs/10-conventions.md).

## Contributing

Contributions are welcome! Please open issues or submit pull requests.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Roadmap

See [Roadmap](./docs/11-roadmap.md) for planned features and improvements.

## Changelog

See [CHANGELOG.md](../../CHANGELOG.md) for release history.
