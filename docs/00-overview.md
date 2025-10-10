<!-- 1d6f2d2c-8a55-4c6c-94e1-9cf0f7c3b4d4 -->
### Overview

- **Purpose**: Encapsulate outbound HTTP calls with clear separation: Caller (intent), Service (execution), Receiver (parsing), DTO (domain model).
- **Core flow**: Controller → `Caller::call()` → `CallService` → Laravel Http → `Receiver::fromResponse()` → DTOs → `toResponse()`.
- **Demo usage**: `src/app/Callers/**`, `src/app/Receivers/**`, `src/app/Dtos/**`.

### Components map

- Callers: `src/raid/caller/src/Callers/**`
- Receivers: `src/raid/caller/src/Receivers/**`
- DTOs: `src/raid/caller/src/Dtos/**`
- Service: `src/raid/caller/src/Services/CallService.php`
- Traits: `src/raid/caller/src/Traits/**`
- Provider: `src/raid/caller/src/Providers/CallerServiceProvider.php`

### Key ideas

- Callers define request method, URL, and options; no parsing.
- Receivers parse `Response` into immutable DTOs; `ResponseReceiver` exposes `toResponse()` and `getStatus()`.
- DTOs are readonly; provide `fromArray()` and `toArray()`.
- `CallService` centralizes execution, defaults, and extension points (retries, caching, observability).


