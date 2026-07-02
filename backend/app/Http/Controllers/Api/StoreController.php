<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function index()
    {
        $stores = DB::table('stores')->orderBy('id')->get();
        return response()->json(['status' => 'success', 'data' => $stores]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:100']);
        $id = DB::table('stores')->insertGetId([
            'name' => $validated['name'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $store = DB::table('stores')->where('id', $id)->first();
        return response()->json(['status' => 'success', 'data' => $store], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['name' => 'required|string|max:100']);
        // Upsert: the frontend uses fixed store slots (ids 1-4). Renaming a slot
        // whose row does not exist yet must CREATE it, otherwise store_id points at
        // a missing store and every FK-constrained retail save fails silently.
        DB::table('stores')->updateOrInsert(
            ['id' => $id],
            ['name' => $validated['name'], 'updated_at' => now(), 'created_at' => now()]
        );
        $store = DB::table('stores')->where('id', $id)->first();
        return response()->json(['status' => 'success', 'data' => $store]);
    }

    public function destroy($id)
    {
        DB::table('stores')->where('id', $id)->delete();
        return response()->json(['status' => 'success']);
    }
}
