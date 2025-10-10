<!-- 9b6f7b32-3e25-4d0b-9d4c-9d1e8a6d3e8f -->
### Conventions

- DTOs are readonly and created via `fromArray()`.
- Receivers do no network IO; only parse `Response` and shape outputs.
- Callers never parse responses; only define method, URL, options, receiver.
- Prefer additive changes; avoid breaking interfaces; guard with tests.


