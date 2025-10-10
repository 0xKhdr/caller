<!-- 2a6f0b2a-94a2-4c9f-b2c3-3f14d2b9a111 -->
### Observability

- **Logging**: caller and receiver `toLog()`; consider redacting sensitive fields.
- **Correlation**: inject request IDs into headers and logs.
- **Metrics**: capture request duration, status codes; expose hooks in `CallService`.
- **Config**: feature flags to enable/disable verbose logging.


