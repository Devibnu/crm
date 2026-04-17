<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class Controller
{
    protected function authorizeAbility(Request $request, string $action, string $subject): void
    {
        $user = $request->user();

        if (! $user || ! $user->canAccess($action, $subject)) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke resource ini.');
        }
    }
}
