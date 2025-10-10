<!-- 0d4b6a24-0c1e-44c1-bdd8-0d5a7d63b2ad -->
### Testing Guidance

- Unit test Receivers with Laravel HTTP fakes.
- Build `Response` via `Http::response($json, $status)` and call `Receiver::fromResponse(...)`.
- Assert `getStatus()` and payload from `toResponse()`.

### Example sketch

```php
Http::fake([
    '*/users*' => Http::response([['id' => 1, 'name' => 'A']], 200),
]);
$r = GetUsersCaller::make(page: 1)->call();
expect($r->getStatus())->toBe(200);
expect($r->toResponse()['data'][0]['name'])->toBe('A');
```


