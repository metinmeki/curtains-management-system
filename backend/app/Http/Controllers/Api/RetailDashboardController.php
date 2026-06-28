<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetailSale;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RetailDashboardController extends Controller
{
    public function index(Request $request, $storeId)
    {
        $user = $request->user();
        if ($user->role === 'cashier' && $user->store_id && (int)$user->store_id !== (int)$storeId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        try {
            $period = $request->input('period', 'today');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $dateRange = $this->getDateRange($period, $startDate, $endDate);

            // Get all sales filtered
            $salesQuery = RetailSale::where('store_id', $storeId);
            if ($dateRange) {
                $salesQuery->whereBetween('created_at', $dateRange);
            }

            $allSales = $salesQuery->get();

            // Calculate stats
            $stats = $this->calculateStats($allSales, $storeId, $dateRange);

            // Get recent sales
            $recentSales = RetailSale::where('store_id', $storeId)
                ->with('client', 'saleItems')
                ->when($dateRange, fn($q) => $q->whereBetween('created_at', $dateRange))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($sale) => [
                    'id' => $sale->id,
                    'client_name' => $sale->client ? $sale->client->name : 'عميل عام',
                    'total_amount' => (float)$sale->total_amount,
                    'paid_amount' => (float)$sale->paid_amount,
                    'remaining_amount' => (float)$sale->remaining_amount,
                    'payment_status' => $sale->payment_status,
                    'created_at' => $sale->created_at->format('Y-m-d'),
                ]);

            // Get recent debts (sales with remaining amount)
            $recentDebts = RetailSale::where('store_id', $storeId)
                ->where('remaining_amount', '>', 0)
                ->with('client')
                ->when($dateRange, fn($q) => $q->whereBetween('created_at', $dateRange))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($sale) => [
                    'client_name' => $sale->client ? $sale->client->name : 'عميل عام',
                    'client_phone' => $sale->client ? $sale->client->phone : '-',
                    'original_amount' => (float)$sale->total_amount,
                    'remaining_amount' => (float)$sale->remaining_amount,
                    'created_at' => $sale->created_at->format('Y-m-d'),
                ]);

            // Get recent expenses
            $recentExpenses = \App\Models\RetailExpense::where('store_id', $storeId)
                ->when($dateRange, fn($q) => $q->whereBetween('date', [$dateRange[0]->format('Y-m-d'), $dateRange[1]->format('Y-m-d')]))
                ->orderBy('date', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($expense) => [
                    'category' => $expense->category,
                    'amount' => (float)$expense->amount,
                    'note' => $expense->note,
                    'date' => $expense->date->format('Y-m-d'),
                ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'stats' => $stats,
                    'recent_sales' => $recentSales,
                    'recent_debts' => $recentDebts,
                    'recent_expenses' => $recentExpenses,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => [
                    'stats' => $this->getEmptyStats(),
                    'recent_sales' => [],
                    'recent_debts' => [],
                    'recent_expenses' => [],
                ]
            ], 500);
        }
    }

    private function getDateRange($period, $startDate = null, $endDate = null)
    {
        $today = Carbon::today();

        switch ($period) {
            case 'today':
                return [$today->copy()->startOfDay(), $today->copy()->endOfDay()];
            case 'week':
                return [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()];
            case 'month':
                return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
            case 'custom':
                if ($startDate && $endDate) {
                    return [
                        Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay(),
                        Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay()
                    ];
                }
                return null;
            default:
                return null;
        }
    }

    private function calculateStats($sales, $storeId, $dateRange = null)
    {
        $totalAmount = $sales->sum('total_amount');
        $paidAmount = $sales->sum('paid_amount');
        $remainingAmount = $sales->sum('remaining_amount');
        $fullPaymentCount = $sales->where('payment_status', 'full')->count();
        $expensesQuery = \App\Models\RetailExpense::where('store_id', $storeId);
        if ($dateRange) {
            $expensesQuery->whereBetween('date', [$dateRange[0]->format('Y-m-d'), $dateRange[1]->format('Y-m-d')]);
        }
        $totalExpenses = $expensesQuery->sum('amount');

        return [
            'total_sales' => (float)$totalAmount ?: 0,
            'total_paid' => (float)$paidAmount ?: 0,
            'total_debt' => (float)$remainingAmount ?: 0,
            'full_payment_sales' => $fullPaymentCount,
            'total_expenses' => (float)$totalExpenses ?: 0,
            'net_amount' => (float)(($totalAmount ?: 0) - ($totalExpenses ?: 0)),
        ];
    }

    private function getEmptyStats()
    {
        return [
            'total_sales' => 0,
            'total_paid' => 0,
            'total_debt' => 0,
            'full_payment_sales' => 0,
            'total_expenses' => 0,
            'net_amount' => 0,
        ];
    }
}
