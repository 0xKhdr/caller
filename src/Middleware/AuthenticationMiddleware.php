<?php

namespace Raid\Caller\Middleware;

use Closure;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthenticationMiddleware
{
    private string $token;

    private string $type;

    private ?Closure $tokenResolver;

    public function __construct(
        ?string $token = null,
        string $type = 'Bearer',
        ?callable $tokenResolver = null
    ) {
        $this->token = $token;
        $this->type = $type;
        $this->tokenResolver = $tokenResolver;
    }

    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        $token = $this->resolveToken();

        if ($token) {
            $request = $request->withHeader('Authorization', "$this->type $token");
        }

        return $next($request);
    }

    private function resolveToken(): ?string
    {
        if ($this->tokenResolver) {
            return ($this->tokenResolver)();
        }

        return $this->token;
    }
}
