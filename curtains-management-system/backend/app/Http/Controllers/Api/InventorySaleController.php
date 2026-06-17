<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryClient;
use App\Models\InventoryDebt;
use App\Models\InventorySale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventorySaleController extends Controller
{
    public function index(Request $request)
    {
        $sales = InventorySale::with(['client:id,name', 'items.category', 'items.productType'])
            ->when($request->client_id, fn($q) => $q->where('inventory_client_id', $request->client_id))
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->category_id, fn($q) => $q->whereHas('items', fn($i) => $i->where('inventory_category_id', $request->category_id)))
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->latest()
            ->paginate(20);

        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'inventory_client_id'          => 'required|exists:inventory_clients,id',
            'items'                        => 'required|array|min:1',
            'items.*.inventory_category_id'     => 'required|exists:inventory_categories,id',
            'items.*.inventory_product_type_id' => 'required|exists:inventory_product_types,id',
            'items.*.quantity'             => 'required|numeric|min:0.01',
            'items.*.unit_price'           => 'required|numeric|min:0',
            'items.*.note'                 => 'nullable|string',
            'payment_status'               => 'required|in:paid,partial,debt',
            'paid_amount'                  => 'required_if:payment_status,partial|nullable|numeric|min:0',
            'notes'                        => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $itemsToCreate = [];

            foreach ($data['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $lineTotal;
                $itemsToCreate[] = [
                    'inventory_category_id'     => $item['inventory_category_id'],
                    'inventory_product_type_id' => $item['inventory_product_type_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total_price' => $lineTotal,
                    'note'        => $item['note'] ?? null,
                ];
            }

            $paidAmount = match ($data['payment_status']) {
                'paid'    => $totalAmount,
                'debt'    => 0,
                'partial' => (float) ($data['paid_amount'] ?? 0),
            };
            $remainingAmount = max(0, $totalAmount - $paidAmount);

            $sale = InventorySale::create([
                'inventory_client_id' => $data['inventory_client_id'],
                'total_amount'        => $totalAmount,
                'paid_amount'         => $paidAmount,
                'remaining_amount'    => $remainingAmount,
                'payment_status'      => $data['payment_status'],
                'notes'               => $data['notes'] ?? null,
                'created_by'          => $request->user()->id,
            ]);

            $sale->items()->createMany($itemsToCreate);

            if (in_array($data['payment_status'], ['debt', 'partial'])) {
                InventoryDebt::create([
                    'inventory_sale_id'   => $sale->id,
                    'inventory_client_id' => $data['inventory_client_id'],
                    'original_amount'     => $remainingAmount,
                    'paid_amount'         => 0,
                    'status'              => 'open',
                    'notes'               => $data['notes'] ?? null,
                    'created_by'          => $request->user()->id,
                ]);
            }

            // Update client balance and last transaction
            InventoryClient::where('id', $data['inventory_client_id'])->update([
                'last_transaction_at' => now(),
            ]);

            DB::commit();

            return response()->json($sale->load(['items.category', 'items.productType', 'debt']), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Sale creation failed.', 'error' => $e->getMessage()], 500);
        }
    }
}
