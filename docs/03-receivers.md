<!-- 0f8e3d67-1d1a-4f71-bb7c-7d9b6f4e1b55 -->
### Receivers

- Contract: `Raid\\Caller\\Receivers\\Contracts\\Receiver` with `static fromResponse(Response): static`.
- Base: `ReceiverAbstract` with `toLog()` and `toArray()`.
- `ResponseReceiver`: adds `toResponse()` and `getStatus()` via `ToResponse` trait.

### Success and error shaping

- Override `toSuccessResponse()` and `toErrorResponse()` in classes extending `ResponseReceiver`.
- Keep a consistent payload shape: `{ message, data? }`.

### Example

```php
readonly class GetUsersReceiver extends ResponseReceiver {
    public function __construct(protected int $status, protected array $users) {}
    public static function fromResponse(Response $r): static {
        return new static(status: $r->status(), users: array_map(fn(array $u) => UserDto::fromArray($u), $r->json()));
    }
    public function toSuccessResponse(): array {
        return ['message' => __('Users fetched successfully'), 'data' => array_map(fn(UserDto $u) => $u->toArray(), $this->users)];
    }
    public function toErrorResponse(): array { return ['message' => __('Failed to fetch users')]; }
}
```


