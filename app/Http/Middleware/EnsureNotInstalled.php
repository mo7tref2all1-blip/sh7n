<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks the /install/* routes shut the moment installation has completed once
 * (storage/installed.lock present). Returns a plain 404 rather than a redirect so an
 * unauthenticated visitor can't even tell the installer ever existed.
 */
class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (file_exists(storage_path('installed.lock'))) {
            abort(404);
        }

        return $next($request);
    }
}
