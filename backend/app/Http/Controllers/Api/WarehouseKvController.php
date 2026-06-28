<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseKvController extends Controller
{
    public function get($key)
    {
        $row = DB::table('warehouse_kv')->where('key', $key)->first();
        if (!$row) return response()->json(['status' => 'success', 'data' => null]);
        return response()->json(['status' => 'success', 'data' => json_decode($row->value, true)]);
    }

    public function set(Request $request, $key)
    {
        $value = json_encode($request->json()->all());
        DB::table('warehouse_kv')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
        return response()->json(['status' => 'success']);
    }
}
