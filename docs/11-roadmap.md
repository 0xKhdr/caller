<!-- d0e3f1b7-2e7b-4b7c-8f2d-2a5fb74a6d3a -->
### Improvements (prioritized)

#### Now

- Defaults in `CallService`: connect/read timeouts; retries with exponential backoff + jitter; 429-aware retry.
  - Acceptance: configurable via config; per-caller overrides; tests simulate timeouts/429.
- GET response cache (opt-in): cache key built from method+URL+normalized query; TTL 30–60s.
  - Acceptance: cache hit/miss tests; bypass on non-GET.
- Filter null query params in callers to avoid sending `null`.
  - Acceptance: options show only non-null values; integration test asserts query string.

#### Next

- ErrorDto + exception mapper: unify error payloads for non-2xx responses.
  - Acceptance: receivers populate ErrorDto; controllers see consistent `{ message, error }`.
- Observability hooks: request ID, timing metrics, structured logs; redaction rules.
  - Acceptance: logs/metrics emitted behind flags; IDs threaded through.

#### Later

- Circuit breaker per-caller: thresholds and cool-downs; fast-fail with clear error.
- Generators/scaffolding: artisan command to scaffold Caller/Receiver/DTO/tests.
- OpenAPI DTO scaffolding: generate initial DTOs from schemas.

### Test plan

- Unit: Receiver behavior with HTTP fakes; DTO mapping; error shapes.
- Feature: Controller responses for success/error; caching behavior.
- Integration: Config-driven retries/backoff; metrics/log toggles.


