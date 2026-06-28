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
            ->where("store_id", $storeId)
            ->orderBy("name")
            ->get();

        foreach ($clients as $client) {
            $client->total_sales = DB::table("retail_sales")
                ->where("client_id", $client->id)
                ->sum("total_amount");
            $client->total_debt = DB::table("retail_sales")
                ->where("client_id", $client->id)
                ->sum("remaining_amount");
            $client->sales_count = DB::table("retail_sales")
                ->where("client_id", $client->id)
                ->count();
        }

        return response()->json(["status" => "success", "data" => $clients]);
    }
}
