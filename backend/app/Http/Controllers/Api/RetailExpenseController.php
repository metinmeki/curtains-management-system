<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RetailExpenseController extends Controller
{
    public function index(Request $request, $storeId)
    {
        $user = $request->user();
        if ($user->role === 'cashier' && $user->store_id && (int)$user->store_id !== (int)$storeId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
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
        $storeId = ($user->role === 'cashier') ? ($user->store_id ?? 1) : ($data["storeId"] ?? $user->store_id ?? 1);

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
