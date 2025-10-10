<!-- 5a3c0f8c-0f8b-4f2e-9d7e-0e1b682f3d3d -->
### Services

- `CallService::call(Caller): Receiver`
  - Resolves receiver class via `Caller::getReceiver()` and calls `::fromResponse(...)`.
  - Delegates HTTP to Laravel `Http::send(method, url, options)`.

### Options and defaults

- Callers supply `getOptions()` (headers, query, json). Avoid nulls in query.
- Recommended defaults (future extension): connect/read timeouts, retries with jitter, 429-aware backoff, optional GET cache.

### Extension points

- Retry policy hooks (jitter, max attempts).
- Caching middleware for idempotent GETs.
- Observability: timing, correlation IDs, structured logs.


