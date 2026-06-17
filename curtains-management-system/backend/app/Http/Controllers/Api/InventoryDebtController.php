<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryDebt;
use App\Models\InventoryDebtPayment;
use App\Models\InventorySale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryDebtController extends Controller
{
    public function index(Request $request)
    {
        $debts = InventoryDebt::with(['client:id,name,phone', 'payments', 'sale:id,created_at,total_amount'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->client_id, fn($q) => $q->where('inventory_client_id', $request->client_id))
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->latest()
            ->paginate(20);

        $debts->getCollection()->transform(function ($debt) {
            $debt->remaining_amount = $debt->remaining_amount;
            return $debt;
        });

        return response()->json($debts);
    }

    public function addPayment(Request $request, int $debt)
    {
        $debtRecord = InventoryDebt::findOrFail($debt);

        if ($debtRecord->status === 'paid') {
            return response()->json(['message' => 'This debt is already fully paid.'], 422);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes'  => 'nullable|string',
        ]);

        $remaining = $debtRecord->remaining_amount;

        if ($data['amount'] > $remaining) {
            return response()->json([
                'message' => 'Payment amount exceeds remaining debt of ' . $remaining,
            ], 422);
        }

        DB::beginTransaction();
        try {
            $payment = InventoryDebtPayment::create([
                'inventory_debt_id' => $debtRecord->id,
                'amount'            => $data['amount'],
                'notes'             => $data['notes'] ?? null,
                'created_by'        => $request->user()->id,
            ]);

            $debtRecord->syncPaidAmountAndStatus();

            $sale = $debtRecord->sale;
            $sale->paid_amount      = $sale->total_amount - $debtRecord->remaining_amount;
            $sale->remaining_amount = $debtRecord->remaining_amount;
            $sale->payment_status   = $debtRecord->status === 'paid' ? 'paid' : 'partial';
            $sale->save();

            DB::commit();

            return response()->json([
                'payment'          => $payment,
                'debt_status'      => $debtRecord->status,
                'remaining_amount' => $debtRecord->remaining_amount,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Payment failed.', 'error' => $e->getMessage()], 500);
        }
    }

    public function paymentHistory(int $debt)
    {
        $debtRecord = InventoryDebt::with(['payments.createdBy:id,name', 'client:id,name'])
            ->findOrFail($debt);

        return response()->json([
            'debt'     => array_merge($debtRecord->toArray(), ['remaining_amount' => $debtRecord->remaining_amount]),
            'payments' => $debtRecord->payments,
        ]);
    }
}
