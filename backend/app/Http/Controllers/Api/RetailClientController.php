<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RetailClientController extends Controller
{
    public function index(Request $request, $storeId)
    {
        $user = $request->user();
        if ($user->role === 'cashier' && $user->store_id && (int)$user->store_id !== (int)$storeId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $clients = DB::table("retail_clients")
            ->where("retail_clients.store_id", $storeId)
            ->leftJoin(
                DB::raw('(SELECT client_id, SUM(total_amount) as total_sales, SUM(remaining_amount) as total_debt, COUNT(*) as sales_count FROM retail_sales GROUP BY client_id) as agg'),
                'retail_clients.id', '=', 'agg.client_id'
            )
            ->select([
                'retail_clients.*',
                DB::raw('COALESCE(agg.total_sales, 0) as total_sales'),
                DB::raw('COALESCE(agg.total_debt, 0) as total_debt'),
                DB::raw('COALESCE(agg.sales_count, 0) as sales_count'),
            ])
            ->orderBy("retail_clients.name")
            ->get();

        return response()->json(["status" => "success", "data" => $clients]);
    }
}
