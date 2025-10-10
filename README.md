### Caller Package

- Purpose: Encapsulate outbound HTTP with clear roles: Caller (intent), Service (execution), Receiver (parsing), DTO (domain model).
- Flow: Controller → Caller::call() → CallService → Laravel Http → Receiver::fromResponse() → DTO → toResponse().

### Installation

- Register `Raid\\Caller\\Providers\\CallerServiceProvider` (auto-discovery if enabled).
- Publish config (tag `caller-pack`) if provided by the package.

### Quick start

```php
readonly class GetUsersCaller extends \\Raid\\Caller\\Callers\\GetCaller {
    public function getUrl(): string { return 'https://api.example.com/users'; }
    public function getReceiver(): string { return GetUsersReceiver::class; }
}

readonly class GetUsersReceiver extends \\Raid\\Caller\\Receivers\\ResponseReceiver {
    public function __construct(protected int $status, protected array $users) {}
    public static function fromResponse(\\Illuminate\\Http\\Client\\Response $r): static {
        return new static(status: $r->status(), users: array_map(fn(array $u) => UserDto::fromArray($u), $r->json()));
    }
    public function toSuccessResponse(): array { return ['message' => 'Users fetched', 'data' => array_map(fn(UserDto $u) => $u->toArray(), $this->users)]; }
    public function toErrorResponse(): array { return ['message' => 'Failed to fetch users']; }
}
```

### Concepts

- Overview: high-level map. See `./docs/00-overview.md`.
- Callers: define method/URL/options/receiver. See `./docs/02-callers.md`.
- Receivers: parse response, shape output. See `./docs/03-receivers.md`.
- DTOs: immutable data model. See `./docs/04-dtos.md`.
- Service: orchestration and extension points. See `./docs/05-services.md`.
- Traits & Provider: utilities and config. See `./docs/06-traits.md`, `./docs/07-providers.md`.

### Lifecycle

- Summary and sequence. See `./docs/01-lifecycle.md`.

### Testing

- Use Laravel HTTP fakes; test Receivers. See `./docs/08-testing.md`.

### Observability & Conventions

- Logging, correlation, metrics; coding norms. See `./docs/09-observability.md`, `./docs/10-conventions.md`.

### Roadmap

- Performance and capability improvements. See `./docs/11-roadmap.md`.


