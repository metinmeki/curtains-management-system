<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseSupplyController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->json()->all();

        if (!empty($data['reference']) && DB::table('warehouse_supplies')->where('reference', $data['reference'])->exists()) {
            $s = DB::table('warehouse_supplies')->where('reference', $data['reference'])->first();
            return response()->json(['status' => 'success', 'id' => $s->id]);
        }

        $storeId = (int)($data['storeId'] ?? 0);
        if ($storeId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Valid store ID required'], 422);
        }
        if (!DB::table('stores')->where('id', $storeId)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Store not found'], 422);
        }

        $items = $data['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            return response()->json(['status' => 'error', 'message' => 'At least one item required'], 422);
        }

        $total     = (float)($data['total'] ?? $data['finalTotal'] ?? $data['subtotal'] ?? 0);
        $paid      = min((float)($data['paid'] ?? 0), $total);
        $remaining = max($total - $paid, 0);
        $status    = $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'debt');

        DB::beginTransaction();
        try {
            $id = DB::table('warehouse_supplies')->insertGetId([
                'store_id'   => $storeId,
                'reference'  => $data['reference'] ?? ('WH-' . time()),
                'date'       => $data['date'] ?? now()->toDateString(),
                'total'      => $total,
                'paid'       => $paid,
                'remaining'  => $remaining,
                'status'     => $status,
                'notes'      => $data['notes'] ?? null,
                'payments'   => json_encode($data['payments'] ?? ($paid > 0 ? [['date' => now()->toDateString(), 'amount' => $paid, 'note' => 'Initial payment']] : [])),
                'created_by' => $user->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $item) {
                $unitPrice    = (float)($item['price'] ?? 0);
                $costPrice    = (float)($item['costPrice'] ?? $item['cost_price'] ?? 0);
                $qty          = (float)($item['quantity'] ?? 0);
                $profitAmount = ($unitPrice - $costPrice) * $qty;
                DB::table('warehouse_supply_items')->insert([
                    'supply_id'     => $id,
                    'item_name'     => $item['item'] ?? $item['item_name'] ?? '',
                    'quantity'      => $qty,
                    'unit'          => $item['unit'] ?? '',
                    'price'         => $unitPrice,
                    'cost_price'    => $costPrice,
                    'profit_amount' => $profitAmount,
                    'line_total'    => (float)($item['lineTotal'] ?? $item['line_total'] ?? 0),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            // Deduct stock
            foreach ($items as $item) {
                $name = $item['item'] ?? $item['item_name'] ?? '';
                $qty  = (float)($item['quantity'] ?? 0);
                if (!$name || !$qty) continue;
                $stock = DB::table('warehouse_stock')->where('item_name', $name)->first();
                if ($stock) {
                    $log = json_decode($stock->movement_log ?? '[]', true) ?: [];
                    $log[] = ['date' => now()->toDateString(), 'delta' => -$qty, 'ref' => $data['reference'] ?? '', 'type' => 'supply'];
                    DB::table('warehouse_stock')->where('item_name', $name)->update([
                        'quantity'     => max(0, $stock->quantity - $qty),
                        'movement_log' => json_encode(array_slice($log, -500)),
                        'updated_at'   => now(),
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to save supply'], 500);
        }

        return response()->json(['status' => 'success', 'id' => $id]);
    }

    public function index(Request $request)
    {
        $storeId = $request->query('store_id');
        $query = DB::table('warehouse_supplies')->orderBy('date', 'desc');
        if ($storeId) $query->where('store_id', $storeId);

        $supplies = $query->get();
        foreach ($supplies as $s) {
            $s->items    = DB::table('warehouse_supply_items')->where('supply_id', $s->id)->get();
            $s->payments = json_decode($s->payments ?? '[]', true) ?: [];
        }
        return response()->json(['status' => 'success', 'data' => $supplies]);
    }

    public function pay(Request $request, $supplyId)
    {
        $supply = DB::table('warehouse_supplies')->find($supplyId);
        if (!$supply) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);

        $amount = min((float)$request->input('amount', 0), $supply->remaining);
        if ($amount <= 0) return response()->json(['status' => 'error', 'message' => 'Invalid amount'], 422);

        $payments   = json_decode($supply->payments ?? '[]', true) ?: [];
        $payments[] = ['date' => now()->toDateString(), 'amount' => $amount, 'note' => $request->input('note', '')];
        $newPaid    = $supply->paid + $amount;
        $newRemaining = max($supply->total - $newPaid, 0);
        $status     = $newRemaining <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'debt');

        DB::table('warehouse_supplies')->where('id', $supplyId)->update([
            'paid'       => $newPaid,
            'remaining'  => $newRemaining,
            'status'     => $status,
            'payments'   => json_encode($payments),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'paid' => $newPaid, 'remaining' => $newRemaining, 'status_val' => $status]);
    }
}
