<?php

namespace Raid\Caller\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class LoggingMiddleware
{
    private ?LoggerInterface $logger;

    private string $logLevel;

    private bool $logSensitiveData;

    public function __construct(
        ?LoggerInterface $logger = null,
        string $logLevel = 'info',
        bool $logSensitiveData = false
    ) {
        $this->logger = $logger ?? app('log');
        $this->logLevel = $logLevel;
        $this->logSensitiveData = $logSensitiveData;
    }

    /**
     * @throws Throwable
     */
    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        $startTime = microtime(true);

        $this->logRequest($request);

        try {
            $response = $next($request);
            $duration = microtime(true) - $startTime;

            $this->logResponse($request, $response, $duration);

            return $response;

        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logError($request, $e, $duration);
            throw $e;
        }
    }

    private function logRequest(RequestInterface $request): void
    {
        $context = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $this->sanitizeHeaders($request->getHeaders()),
        ];

        if ($this->logSensitiveData) {
            $body = (string) $request->getBody();
            if (! empty($body)) {
                $context['body'] = $body;
            }
        }

        $this->logger->{$this->logLevel}('API Request', $context);
    }

    private function logResponse(RequestInterface $request, ResponseInterface $response, float $duration): void
    {
        $context = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round($duration * 1000, 2),
            'response_headers' => $this->sanitizeHeaders($response->getHeaders()),
        ];

        $logLevel = $response->getStatusCode() >= 400 ? 'warning' : $this->logLevel;

        $this->logger->{$logLevel}('API Response', $context);
    }

    private function logError(RequestInterface $request, Throwable $e, float $duration): void
    {
        $context = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'duration_ms' => round($duration * 1000, 2),
            'error' => $e->getMessage(),
            'exception' => get_class($e),
        ];

        $this->logger->error('API Request Failed', $context);
    }

    private function sanitizeHeaders(array $headers): array
    {

        return array_map(function ($values, $name) {
            $sensitiveHeaders = ['authorization', 'cookie', 'set-cookie', 'proxy-authorization'];
            if (in_array(strtolower($name), $sensitiveHeaders) && ! $this->logSensitiveData) {
                return ['***'];
            }

            return $values;
        }, $headers, array_keys($headers));
    }
}
