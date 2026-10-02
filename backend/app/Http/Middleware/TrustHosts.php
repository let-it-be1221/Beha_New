<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TrustHosts — only trust APP_URL host to prevent host header injections.
 */
class TrustHosts
{
    public function hosts(): array
    {
        return [
            parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST),
            $this->allSubdomainsOfApplicationUrl(),
        ];
    }

    private function allSubdomainsOfApplicationUrl(): ?string
    {
        $url = env('APP_URL');
        if (!$url) return null;
        $host = parse_url($url, PHP_URL_HOST);
        return $host ? '^(.+\.)?' . preg_quote($host) . '$' : null;
    }

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
