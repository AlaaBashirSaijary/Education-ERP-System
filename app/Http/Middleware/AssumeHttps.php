<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * For free tunnels (share-demo.sh) that serve https to the visitor but reach us over plain http without
 * saying so (no X-Forwarded-Proto). Without this, every generated link would be http:// and the browser
 * would block the stylesheet and scripts on the https page. Off unless ASSUME_HTTPS=true.
 */
class AssumeHttps
{
    public function handle(Request $request, Closure $next)
    {
        if (config('school.assume_https') && $this->isPublicHostname($request->getHost())) {
            $request->server->set('HTTPS', 'on');
            URL::forceScheme('https');
        }

        return $next($request);
    }

    /** localhost, IP addresses (LAN testing over http) and *.local are never treated as https. */
    private function isPublicHostname(string $host): bool
    {
        return $host !== 'localhost'
            && ! str_ends_with($host, '.local')
            && ! filter_var(trim($host, '[]'), FILTER_VALIDATE_IP);
    }
}
