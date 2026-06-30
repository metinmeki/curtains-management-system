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

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['name' => 'required|string|max:100']);
        DB::table('stores')->where('id', $id)->update(['name' => $validated['name'], 'updated_at' => now()]);
        $store = DB::table('stores')->where('id', $id)->first();
        return response()->json(['status' => 'success', 'data' => $store]);
    }
}
