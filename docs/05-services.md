<!-- 5a3c0f8c-0f8b-4f2e-9d7e-0e1b682f3d3d -->
### Services

- `CallService::call(Caller): Receiver`
  - Resolves receiver class via `Caller::getReceiver()` and calls `::fromResponse(...)`.
  - Delegates HTTP to Laravel `Http::send(method, url, options)` with configurable timeouts and retries.

### Options and defaults

- Callers supply `getOptions()` (headers, query, json). Avoid nulls in query.
- Defaults via `config('caller.*')`:
  - Timeouts: `http.timeout`, `http.connect_timeout`.
  - Retries: `retry.enabled`, `max_attempts`, `base_delay_ms`, `max_delay_ms`, `jitter`, retry on 5xx/429/connection.
  - Optional GET cache: `cache.enabled`, `cache.ttl_seconds` or per-caller `['caller' => ['cache' => true]]`.

### Extension points

- Retry policy hooks (jitter, max attempts).
- Caching middleware for idempotent GETs.
- Observability: timing, correlation IDs, structured logs.

### Caller meta options

- You may pass a `caller` meta array inside options to control behaviors without affecting the HTTP request:
```php
return [
  'query' => Query::filterNulls($q),
  'caller' => [
    'cache' => true, // opt-in GET cache
  ],
];
```



