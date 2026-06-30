<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemTypeController extends Controller
{
    public function index()
    {
        $items = DB::table('item_types')->orderBy('name')->get();
        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function store(Request $request)
    {
        $data = $request->json()->all();

        $code = strtoupper(trim($data['code'] ?? ''));
        if (!$code) {
            return response()->json(['status' => 'error', 'message' => 'Code is required'], 422);
        }
        if (DB::table('item_types')->where('code', $code)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Code already exists'], 422);
        }

        $id = DB::table('item_types')->insertGetId([
            'code'              => $code,
            'name'              => trim($data['name'] ?? ''),
            'cost_price'        => (float)($data['cost_price'] ?? 0),
            'selling_price'     => (float)($data['selling_price'] ?? 0),
            'quantity'          => (float)($data['quantity'] ?? 0),
            'unit'              => $data['unit'] ?? 'meter',
            'affects_sale_total'=> !empty($data['affects_sale_total']) ? 1 : 0,
            'worker_account'    => $data['worker_account'] ?? null,
            'is_active'         => 1,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return response()->json(['status' => 'success', 'id' => $id]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->json()->all();

        $code = strtoupper(trim($data['code'] ?? ''));
        if ($code && DB::table('item_types')->where('code', $code)->where('id', '!=', $id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Code already used by another item'], 422);
        }

        DB::table('item_types')->where('id', $id)->update([
            'code'              => $code ?: DB::raw('code'),
            'name'              => trim($data['name'] ?? ''),
            'cost_price'        => (float)($data['cost_price'] ?? 0),
            'selling_price'     => (float)($data['selling_price'] ?? 0),
            'quantity'          => (float)($data['quantity'] ?? 0),
            'unit'              => $data['unit'] ?? 'meter',
            'affects_sale_total'=> !empty($data['affects_sale_total']) ? 1 : 0,
            'worker_account'    => $data['worker_account'] ?? null,
            'is_active'         => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            'updated_at'        => now(),
        ]);

        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        DB::table('item_types')->where('id', $id)->delete();
        return response()->json(['status' => 'success']);
    }

    /** Returns cost_price for a given item name — used by POS when building sale */
    public function costByName(Request $request)
    {
        $name = $request->input('name', '');
        $item = DB::table('item_types')->where('name', $name)->first();
        if (!$item) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        return response()->json(['status' => 'success', 'cost_price' => (float)$item->cost_price, 'selling_price' => (float)$item->selling_price]);
    }
}
