<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInventoryAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Only owners and inventory users can access inventory routes
        if (!in_array($user->role, ['owner', 'inventory_user'])) {
            return response()->json(['message' => 'Forbidden. You do not have access to the inventory module.'], 403);
        }

        return $next($request);
    }
}
