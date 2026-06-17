<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryDebt;
use App\Models\InventorySale;
use Illuminate\Http\Request;

class InventoryDashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = InventorySale::query()
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->when($request->period === 'today', fn($q) => $q->whereDate('created_at', today()))
            ->when($request->period === 'week', fn($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
            ->when($request->period === 'month', fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
            ->when($request->client_id, fn($q) => $q->where('inventory_client_id', $request->client_id));

        $totalSales   = (clone $query)->sum('total_amount');
        $totalPaid    = (clone $query)->sum('paid_amount');
        $totalDebts   = InventoryDebt::whereIn('status', ['open', 'partial'])
            ->selectRaw('SUM(original_amount - paid_amount) as remaining')
            ->value('remaining') ?? 0;

        $salesByCategory = InventorySale::join('inventory_sale_items', 'inventory_sales.id', '=', 'inventory_sale_items.inventory_sale_id')
            ->join('inventory_categories', 'inventory_sale_items.inventory_category_id', '=', 'inventory_categories.id')
            ->when($request->start_date, fn($q) => $q->whereDate('inventory_sales.created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('inventory_sales.created_at', '<=', $request->end_date))
            ->selectRaw('inventory_categories.name as category, SUM(inventory_sale_items.total_price) as total')
            ->groupBy('inventory_categories.name')
            ->get();

        $recentSales = InventorySale::with(['client:id,name', 'items.category', 'items.productType'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => [
                'total_sales'  => $totalSales,
                'total_paid'   => $totalPaid,
                'total_debts'  => $totalDebts,
            ],
            'sales_by_category' => $salesByCategory,
            'recent_sales'      => $recentSales,
        ]);
    }
}
