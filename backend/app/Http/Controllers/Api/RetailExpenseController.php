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
        $user    = $request->user();
        $data    = $request->json()->all();
        $storeId = $user->store_id ?? ($data["storeId"] ?? 1);

        $id = DB::table("retail_expenses")->insertGetId([
            "store_id"   => $storeId,
            "category"   => $data["category"] ?? "General",
            "amount"     => $data["amount"] ?? 0,
            "note"       => $data["note"] ?? null,
            "date"       => $data["date"] ?? now()->toDateString(),
            "created_by" => $user->name,
            "created_at" => now(),
            "updated_at" => now()
        ]);
        return response()->json(["status" => "success", "id" => $id]);
    }
}
