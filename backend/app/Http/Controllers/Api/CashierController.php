<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CashierController extends Controller
{
    public function index()
    {
        $cashiers = User::where('role', 'cashier')->orderBy('created_at')->get(['id', 'name', 'store_id', 'created_at']);
        return response()->json(['status' => 'success', 'data' => $cashiers]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'pin'      => 'nullable|string|max:10',
            'store_id' => 'nullable|integer',
        ]);

        $name     = $validated['name'];
        $pin      = $validated['pin'] ?: '0000';
        $storeId  = $validated['store_id'] ?? null;
        $email    = strtolower(preg_replace('/\s+/', '', $name)) . '@curtains.com';

        $user = User::where('email', $email)->first();
        if ($user) {
            $user->password = Hash::make($pin);
            $user->store_id = $storeId;
            $user->role     = 'cashier';
            $user->save();
        } else {
            $user = User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make($pin),
                'store_id' => $storeId,
                'role'     => 'cashier',
            ]);
        }

        return response()->json(['status' => 'success', 'data' => $user->only(['id', 'name', 'store_id', 'created_at'])], 201);
    }

    public function destroy($id)
    {
        User::where('id', $id)->where('role', 'cashier')->delete();
        return response()->json(['status' => 'success']);
    }
}
