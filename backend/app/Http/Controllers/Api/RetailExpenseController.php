<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RetailExpenseController extends Controller
{
    public function index($storeId)
    {
        $expenses = DB::table("retail_expenses")
            ->where("store_id", $storeId)
            ->orderBy("date", "desc")
            ->get();
        return response()->json(["status" => "success", "data" => $expenses]);
    }

    public function store(Request $request)
    {
        $data = $request->json()->all();
        $id = DB::table("retail_expenses")->insertGetId([
            "store_id" => $data["storeId"] ?? 1,
            "category" => $data["category"] ?? "General",
            "amount" => $data["amount"] ?? 0,
            "note" => $data["note"] ?? null,
            "date" => $data["date"] ?? now()->toDateString(),
            "created_by" => $data["createdBy"] ?? "Admin",
            "created_at" => now(),
            "updated_at" => now()
        ]);
        return response()->json(["status" => "success", "id" => $id]);
    }
}
