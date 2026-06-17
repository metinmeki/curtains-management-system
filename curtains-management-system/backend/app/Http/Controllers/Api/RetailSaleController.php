<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetailClient;
use App\Models\RetailDebt;
use App\Models\RetailSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RetailSaleController extends Controller
{
    public function index(Request $request, int $store)
    {
        $sales = RetailSale::where('store_id', $store)
            ->with(['client:id,name,phone', 'items'])
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->client_id, fn($q) => $q->where('retail_client_id', $request->client_id))
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->latest()
            ->paginate(20);

        return response()->json($sales);
    }

    public function store(Request $request, int $store)
    {
        $data = $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.material'    => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.note'        => 'nullable|string',
            'discount_amount'     => 'nullable|numeric|min:0',
            'discount_note'       => 'nullable|string',
            'payment_status'      => 'required|in:paid,partial,debt',
            'paid_amount'         => 'required_if:payment_status,partial|nullable|numeric|min:0',
            'client_name'         => 'required_if:payment_status,debt|required_if:payment_status,partial|nullable|string',
            'client_phone'        => 'nullable|string',
            'save_client'         => 'nullable|boolean',
            'retail_client_id'    => 'nullable|exists:retail_clients,id',
            'notes'               => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $itemsToCreate = [];

            foreach ($data['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $lineTotal;
                $itemsToCreate[] = [
                    'material'    => $item['material'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total_price' => $lineTotal,
                    'note'        => $item['note'] ?? null,
                ];
            }

            $discountAmount = $data['discount_amount'] ?? 0;
            $finalAmount    = max(0, $totalAmount - $discountAmount);

            $paidAmount = match ($data['payment_status']) {
                'paid'    => $finalAmount,
                'debt'    => 0,
                'partial' => (float) ($data['paid_amount'] ?? 0),
            };
            $remainingAmount = max(0, $finalAmount - $paidAmount);

            // Resolve or create client
            $clientId = $data['retail_client_id'] ?? null;
            $clientName  = $data['client_name'] ?? null;
            $clientPhone = $data['client_phone'] ?? null;

            if (!$clientId && ($data['save_client'] ?? false) && $clientName) {
                $client = RetailClient::create([
                    'store_id'   => $store,
                    'name'       => $clientName,
                    'phone'      => $clientPhone,
                    'created_by' => $request->user()->id,
                    'last_transaction_at' => now(),
                ]);
                $clientId = $client->id;
            } elseif ($clientId) {
                RetailClient::where('id', $clientId)->update(['last_transaction_at' => now()]);
                $client = RetailClient::find($clientId);
                $clientName  = $client->name;
                $clientPhone = $client->phone;
            }

            $sale = RetailSale::create([
                'store_id'        => $store,
                'retail_client_id'=> $clientId,
                'client_name'     => $clientName,
                'client_phone'    => $clientPhone,
                'total_amount'    => $totalAmount,
                'discount_amount' => $discountAmount,
                'discount_note'   => $data['discount_note'] ?? null,
                'final_amount'    => $finalAmount,
                'paid_amount'     => $paidAmount,
                'remaining_amount'=> $remainingAmount,
                'payment_status'  => $data['payment_status'],
                'notes'           => $data['notes'] ?? null,
                'created_by'      => $request->user()->id,
            ]);

            $sale->items()->createMany($itemsToCreate);

            // Create debt record for partial or full debt sales
            if (in_array($data['payment_status'], ['debt', 'partial'])) {
                RetailDebt::create([
                    'store_id'         => $store,
                    'retail_sale_id'   => $sale->id,
                    'retail_client_id' => $clientId,
                    'client_name'      => $clientName,
                    'client_phone'     => $clientPhone,
                    'original_amount'  => $remainingAmount,
                    'paid_amount'      => 0,
                    'status'           => 'open',
                    'notes'            => $data['notes'] ?? null,
                    'created_by'       => $request->user()->id,
                ]);
            }

            DB::commit();

            return response()->json($sale->load(['items', 'debt']), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Sale creation failed.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(int $store, int $sale)
    {
        $sale = RetailSale::where('store_id', $store)
            ->with(['client', 'items', 'debt.payments'])
            ->findOrFail($sale);

        return response()->json($sale);
    }
}
