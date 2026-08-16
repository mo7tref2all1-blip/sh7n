<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Restricts a route group to one or more of the User::TYPE_* portal roles. */
class EnsureUserType
{
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || ! in_array($user->user_type, $types, true)) {
            abort(403, 'لا تملك صلاحية الوصول لهذه الصفحة.');
        }

        return $next($request);
    }
}
