<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Inventory users cannot access retail store routes
        if ($user->role === 'inventory_user') {
            return response()->json(['message' => 'Forbidden. Inventory users cannot access store routes.'], 403);
        }

        $storeId = $request->route('store');

        if (!$storeId) {
            return response()->json(['message' => 'Store not specified.'], 400);
        }

        if (!$user->canAccessStore((int) $storeId)) {
            return response()->json(['message' => 'Forbidden. You do not have access to this store.'], 403);
        }

        return $next($request);
    }
}
