<!-- 6a1aa1a7-8a2c-4a0e-8c5a-1b03b0f86d4e -->
### DTOs

- **Contract/Base**: `Dto`, `DtoAbstract` with `toArray()`, helpers `has()`, `get()`.
- **Principles**: readonly, immutable; map only required fields; safe defaults.
- **Factory**: `static fromArray(array $data): static`.

### Example

```php
readonly class UserDto extends DtoAbstract {
    public function __construct(protected ?string $id, protected ?string $name) {}
    public static function fromArray(array $d): static { return new static(id: Arr::get($d, 'id'), name: Arr::get($d, 'name')); }
    public function toArray(): array { return ['external_id' => $this->id, 'name' => $this->name]; }
}
```


