<!-- c5f2a7e9-2c04-41c2-a2f1-9a5a9a1c6a8a -->
### Provider

- `CallerServiceProvider`
  - Uses `InteractsWithProvider` to publish and merge config under tag `caller-pack`.
  - Hook: `register()` publishes/merges; `boot()` is reserved for runtime wiring.


