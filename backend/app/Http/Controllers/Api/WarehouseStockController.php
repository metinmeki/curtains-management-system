<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseStockController extends Controller
{
    public function index()
    {
        $stock = DB::table('warehouse_stock')->orderBy('item_name')->get();
        foreach ($stock as $s) {
            $s->movement_log = json_decode($s->movement_log ?? '[]', true) ?: [];
        }
        return response()->json(['status' => 'success', 'data' => $stock]);
    }

    public function adjust(Request $request)
    {
        $name   = $request->input('item_name');
        $delta  = (float)$request->input('delta', 0); // positive = add, negative = remove
        $unit   = $request->input('unit', 'قطعة');
        $minQty = (float)$request->input('min_quantity', 10);
        $note   = $request->input('note', '');
        $type   = $request->input('type', 'manual'); // manual, add, remove, damaged

        if (!$name) return response()->json(['status' => 'error', 'message' => 'item_name required'], 422);

        $stock = DB::table('warehouse_stock')->where('item_name', $name)->first();
        $currentQty = $stock ? (float)$stock->quantity : 0;
        $newQty = max(0, $currentQty + $delta);

        $log = $stock ? (json_decode($stock->movement_log ?? '[]', true) ?: []) : [];
        $log[] = [
            'date'  => now()->toDateString(),
            'delta' => $delta,
            'type'  => $type,
            'note'  => $note,
        ];

        if ($stock) {
            DB::table('warehouse_stock')->where('item_name', $name)->update([
                'quantity'     => $newQty,
                'min_quantity' => $minQty,
                'unit'         => $unit,
                'movement_log' => json_encode(array_slice($log, -500)),
                'updated_at'   => now(),
            ]);
        } else {
            DB::table('warehouse_stock')->insert([
                'item_name'    => $name,
                'unit'         => $unit,
                'quantity'     => $newQty,
                'min_quantity' => $minQty,
                'movement_log' => json_encode($log),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        return response()->json(['status' => 'success', 'quantity' => $newQty]);
    }

    public function seed(Request $request)
    {
        // Called once to initialize stock items in DB from the fixed WH_ITEMS list
        $items = $request->input('items', []);
        foreach ($items as $item) {
            $name = $item['item_name'] ?? '';
            if (!$name || DB::table('warehouse_stock')->where('item_name', $name)->exists()) continue;
            DB::table('warehouse_stock')->insert([
                'item_name'    => $name,
                'unit'         => $item['unit'] ?? 'قطعة',
                'quantity'     => (float)($item['quantity'] ?? 0),
                'min_quantity' => (float)($item['min_quantity'] ?? 10),
                'movement_log' => json_encode([]),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
        return response()->json(['status' => 'success']);
    }
}
