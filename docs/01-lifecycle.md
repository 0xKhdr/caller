<!-- 7b4db8a2-3f30-4a2d-8f0e-2a64eeb2f6f1 -->
### Request lifecycle

1. Controller/service constructs a Caller via `::make(...)`.
2. `Caller::call()` delegates to `CallService`.
3. `CallService` executes Laravel Http `send(method, url, options)`.
4. `Receiver::fromResponse(Response)` builds immutable object(s) with DTOs.
5. If using `ResponseReceiver`, consumer reads `getStatus()` and `toResponse()`.

### Contracts touchpoints

- `Caller`: `getMethod()`, `getUrl()`, `getOptions()`, `getReceiver()`.
- `CallService`: `call(Caller): Receiver`.
- `Receiver`: `static fromResponse(Response): static`.
- `ResponseReceiver`: adds `toResponse()` and `getStatus()`.

### Example (high level)

```php
$receiver = GetPostsCaller::make(page: 1)->call();
return response()->json($receiver->toResponse(), $receiver->getStatus());
```


