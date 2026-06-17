<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetailDebt;
use App\Models\RetailExpense;
use App\Models\RetailSale;
use App\Models\DebtPayment;
use Illuminate\Http\Request;

class RetailDashboardController extends Controller
{
    public function index(Request $request, int $store)
    {
        $query = $this->dateFilter($request, $store);

        $totalSales       = (clone $query)->sum('final_amount');
        $paidSales        = (clone $query)->where('payment_status', 'paid')->sum('final_amount');
        $debtSales        = (clone $query)->where('payment_status', 'debt')->sum('final_amount');
        $partialSales     = (clone $query)->where('payment_status', 'partial')->sum('final_amount');
        $totalDiscounts   = (clone $query)->sum('discount_amount');
        $totalPaidAmount  = (clone $query)->sum('paid_amount');

        $totalExpenses = RetailExpense::where('store_id', $store)
            ->when($request->start_date, fn($q) => $q->whereDate('expense_date', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('expense_date', '<=', $request->end_date))
            ->sum('amount');

        $totalOutstandingDebt = RetailDebt::where('store_id', $store)
            ->whereIn('status', ['open', 'partial'])
            ->selectRaw('SUM(original_amount - paid_amount) as remaining')
            ->value('remaining') ?? 0;

        $netAfterExpenses = $totalPaidAmount - $totalExpenses;

        $recentSales = RetailSale::where('store_id', $store)
            ->with(['client:id,name', 'items:id,retail_sale_id,material,quantity,unit_price,total_price'])
            ->latest()
            ->limit(10)
            ->get();

        $recentDebts = RetailDebt::where('store_id', $store)
            ->whereIn('status', ['open', 'partial'])
            ->with('payments')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($d) => array_merge($d->toArray(), ['remaining_amount' => $d->remaining_amount]));

        $recentExpenses = RetailExpense::where('store_id', $store)
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => [
                'total_sales'          => $totalSales,
                'paid_sales'           => $paidSales,
                'debt_sales'           => $debtSales,
                'partial_sales'        => $partialSales,
                'total_outstanding_debt' => $totalOutstandingDebt,
                'total_expenses'       => $totalExpenses,
                'net_after_expenses'   => $netAfterExpenses,
                'total_discounts'      => $totalDiscounts,
            ],
            'recent_sales'    => $recentSales,
            'recent_debts'    => $recentDebts,
            'recent_expenses' => $recentExpenses,
        ]);
    }

    private function dateFilter(Request $request, int $storeId)
    {
        return RetailSale::where('store_id', $storeId)
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->when($request->period === 'today', fn($q) => $q->whereDate('created_at', today()))
            ->when($request->period === 'week', fn($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
            ->when($request->period === 'month', fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year));
    }
}
