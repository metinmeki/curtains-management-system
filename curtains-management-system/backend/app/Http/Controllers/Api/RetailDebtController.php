<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DebtPayment;
use App\Models\RetailDebt;
use App\Models\RetailSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RetailDebtController extends Controller
{
    public function index(Request $request, int $store)
    {
        $debts = RetailDebt::where('store_id', $store)
            ->with(['payments', 'sale:id,created_at,final_amount', 'client:id,name,phone'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->client_id, fn($q) => $q->where('retail_client_id', $request->client_id))
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->latest()
            ->paginate(20);

        // Append computed remaining_amount to each debt
        $debts->getCollection()->transform(function ($debt) {
            $debt->remaining_amount = $debt->remaining_amount;
            return $debt;
        });

        return response()->json($debts);
    }

    public function addPayment(Request $request, int $store, int $debt)
    {
        $debtRecord = RetailDebt::where('store_id', $store)->findOrFail($debt);

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
            $payment = DebtPayment::create([
                'retail_debt_id' => $debtRecord->id,
                'amount'         => $data['amount'],
                'notes'          => $data['notes'] ?? null,
                'created_by'     => $request->user()->id,
            ]);

            $debtRecord->syncPaidAmountAndStatus();

            // Sync sale paid/remaining amounts
            $sale = $debtRecord->sale;
            $totalDebtPaid = $debtRecord->paid_amount;
            $sale->paid_amount      = $sale->final_amount - $debtRecord->remaining_amount;
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

    public function paymentHistory(int $store, int $debt)
    {
        $debtRecord = RetailDebt::where('store_id', $store)
            ->with(['payments.createdBy:id,name'])
            ->findOrFail($debt);

        return response()->json([
            'debt'     => array_merge($debtRecord->toArray(), ['remaining_amount' => $debtRecord->remaining_amount]),
            'payments' => $debtRecord->payments,
        ]);
    }
}
