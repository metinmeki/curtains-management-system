<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RetailKvController extends Controller
{
    public function get(Request $request, $storeId, $key)
    {
        $user = $request->user();
        if ($user->role === 'cashier' && $user->store_id && (int)$user->store_id !== (int)$storeId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $row = DB::table('retail_kv')->where('store_id', $storeId)->where('key', $key)->first();
        $value = $row ? json_decode($row->value, true) : null;
        return response()->json(['status' => 'success', 'data' => $value]);
    }

    public function set(Request $request, $storeId, $key)
    {
        $user = $request->user();
        if ($user->role === 'cashier' && $user->store_id && (int)$user->store_id !== (int)$storeId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $value = json_encode($request->json()->all());

        DB::table('retail_kv')->updateOrInsert(
            ['store_id' => $storeId, 'key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );

        return response()->json(['status' => 'success']);
    }
}
