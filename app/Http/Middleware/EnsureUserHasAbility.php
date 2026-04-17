<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureUserHasAbility
{
    public function handle(Request $request, Closure $next, string $action, string $subject): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccess($action, $subject)) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke resource ini.');
        }

        return $next($request);
    }
}