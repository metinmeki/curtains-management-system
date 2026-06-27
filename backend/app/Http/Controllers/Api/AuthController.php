<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:1',
            'store_id' => 'nullable|integer',
            'role'     => 'nullable|string|in:admin,cashier',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'store_id' => $validated['store_id'] ?? null,
            'role'     => $validated['role'] ?? 'cashier',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data'   => ['user' => $user, 'token' => $token],
        ], 201);
    }

    public function cashierLogin(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string',
            'pin'      => 'nullable|string',
            'store_id' => 'nullable|integer',
        ]);

        $email    = strtolower(preg_replace('/\s+/', '', $validated['name'])) . '@curtains.com';
        $password = $validated['pin'] ?: '0000';
        $storeId  = $validated['store_id'] ?? null;

        $user = User::where('email', $email)->first();

        if ($user) {
            // Always sync the PIN and store_id so it stays up to date
            $user->password = Hash::make($password);
            $user->store_id = $storeId;
            $user->role     = 'cashier';
            $user->save();
        } else {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $email,
                'password' => Hash::make($password),
                'store_id' => $storeId,
                'role'     => 'cashier',
            ]);
        }

        $token = $user->createToken('cashier_token')->plainTextToken;

        return response()->json(['status' => 'success', 'data' => ['token' => $token, 'user' => $user]]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user(),
        ]);
    }
}
