<!-- f2b4c9f0-9a2f-4d58-9d53-4c7b3a8a9a6a -->
### Callers

- **Contract**: `Raid\Caller\Callers\Contracts\Caller`
  - `call(): Receiver`, `getMethod(): string`, `getUrl(): string`, `getOptions(): array`, `getReceiver(): string`.
- **Base**: `CallerAbstract`
  - Implements `call()` via `CallService`; provides `toLog()`; default empty `getOptions()`.
- **Helpers**: `GetCaller`, `PostCaller`, `PutCaller`, `PatchCaller`, `DeleteCaller` override `getMethod()`.

### Usage pattern

- Implement `getUrl()` and `getReceiver()`. Override `getOptions()` for headers, query, json.
- Filter out null query params to avoid sending `null` values.

### Example

```php
readonly class GetPostsCaller extends GetCaller {
    public function __construct(protected ?int $page = 1, protected ?int $userId = null) {}
    public function getUrl(): string { return AppUtility::getJsonPlaceholderUrl('/posts'); }
    public function getOptions(): array { return ['query' => array_filter(['_page' => $this->page, 'userId' => $this->userId], fn($v) => $v !== null)]; }
    public function getReceiver(): string { return GetPostsReceiver::class; }
}
```


