<?php

namespace Raid\Caller\Middleware;

use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;

class CacheMiddleware
{
    public function __construct(
        private readonly ?int $defaultTtl = 3600,
        private readonly ?string $cacheKey = null,
        private readonly array $cacheableMethods = ['GET', 'HEAD'],
        private ?CacheRepository $cache = null,
    ) {
        $this->cache = $cache ?? app('cache');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        // Only cache GET and HEAD requests
        if (! in_array($request->getMethod(), $this->cacheableMethods)) {
            return $next($request);
        }

        $cacheKey = $this->cacheKey ?? $this->generateCacheKey($request);
        $cachedResponse = $this->cache->get($cacheKey);

        if ($cachedResponse) {
            return $this->deserializeResponse($cachedResponse);
        }

        $response = $next($request);

        // Only cache successful responses
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $ttl = $this->getTtlFromResponse($response) ?? $this->defaultTtl;

            if ($ttl > 0) {
                $this->cache->put(
                    $cacheKey,
                    $this->serializeResponse($response),
                    $ttl
                );
            }
        }

        return $response;
    }

    private function generateCacheKey(RequestInterface $request): string
    {
        return 'caller:'.md5(
            $request->getMethod().'|'.
            $request->getUri().'|'.
            ($request->getBody() ? md5((string) $request->getBody()) : '')
        );
    }

    private function serializeResponse(ResponseInterface $response): string
    {
        return serialize([
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => (string) $response->getBody(),
            'version' => $response->getProtocolVersion(),
            'reason' => $response->getReasonPhrase(),
        ]);
    }

    private function deserializeResponse(string $cached): ResponseInterface
    {
        $data = unserialize($cached);

        return new Response(
            $data['status'],
            $data['headers'],
            $data['body'],
            $data['version'],
            $data['reason']
        );
    }

    private function getTtlFromResponse(ResponseInterface $response): ?int
    {
        $cacheControl = $response->getHeaderLine('Cache-Control');

        if (preg_match('/max-age=(\d+)/', $cacheControl, $matches)) {
            return (int) $matches[1];
        }

        $expires = $response->getHeaderLine('Expires');
        if ($expires) {
            $expiresTime = strtotime($expires);
            $currentTime = time();

            if ($expiresTime > $currentTime) {
                return $expiresTime - $currentTime;
            }
        }

        return null;
    }
}
