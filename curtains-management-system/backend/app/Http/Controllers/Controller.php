<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index($store_id)
    {
        // Calculate the stats using Laravel's Query Builder
        $totalSales = DB::table('sales')->where('store_id', $store_id)->sum('total_amount');
        $totalDebts = DB::table('clients')->where('store_id', $store_id)->sum('total_debt');
        $totalExpenses = DB::table('expenses')->where('store_id', $store_id)->sum('amount');

        // Send the data back to the Frontend as JSON
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_sales' => $totalSales,
                'total_debts' => $totalDebts,
                'total_expenses' => $totalExpenses
            ]
        ]);
    }
}