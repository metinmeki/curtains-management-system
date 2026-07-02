<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RetailSaleController extends Controller
{
    public function store(Request $request)
    {
        $user    = $request->user();
        $data    = $request->json()->all();

        // Cashiers: always use their assigned store. Admins: use the storeId from request.
        $request->validate([
            'total'   => 'sometimes|numeric|min:0',
            'paid'    => 'sometimes|numeric|min:0',
            'discount'=> 'sometimes|numeric|min:0',
        ]);

        $storeId = ($user->role === 'cashier') ? ($user->store_id ?? 1) : ($data["storeId"] ?? $user->store_id ?? 1);

        $clientName  = $data["clientName"] ?? "One-time customer";
        $clientPhone = $data["clientPhone"] ?? "-";

        $client = DB::table("retail_clients")
            ->where("store_id", $storeId)
            ->where("name", $clientName)
            ->first();

        if (!$client) {
            $clientId = DB::table("retail_clients")->insertGetId([
                "store_id"   => $storeId,
                "name"       => $clientName,
                "phone"      => $clientPhone,
                "created_at" => now(),
                "updated_at" => now()
            ]);
        } else {
            $clientId = $client->id;
        }

        $saleId = DB::table("retail_sales")->insertGetId([
            "store_id"        => $storeId,
            "client_id"       => $clientId,
            "sale_date"       => $data["date"] ?? now()->toDateString(),
            "total_amount"    => $data["total"] ?? 0,
            "paid_amount"     => $data["paid"] ?? 0,
            "remaining_amount"=> $data["remaining"] ?? 0,
            "payment_status"  => $this->mapStatus($data["status"] ?? "partial"),
            "discount_amount" => $data["discount"] ?? 0,
            "discount_note"   => $data["discountNote"] ?? null,
            "notes"           => $data["note"] ?? null,
            "sale_details"    => $data["saleDetails"] ?? null,
            "worker_accounts" => !empty($data["workerAccounts"]) ? json_encode($data["workerAccounts"]) : null,
            "created_by"      => $user->name,
            "created_at"      => now(),
            "updated_at"      => now()
        ]);

        // Build cost price lookup from item_types (keyed by name)
        $itemTypeCosts = DB::table('item_types')
            ->pluck('cost_price', 'name')
            ->map(fn($v) => (float)$v)
            ->all();

        if (!empty($data["items"])) {
            foreach ($data["items"] as $item) {
                if (($item["quantity"] ?? 0) > 0 && ($item["price"] ?? 0) > 0) {
                    $materialName = $item["material"] ?? "Unknown";
                    $unitPrice    = (float)($item["price"] ?? 0);
                    $quantity     = (float)($item["quantity"] ?? 0);
                    // Snapshot cost price: use item's own costPrice if sent, else look up from catalog
                    $costPrice    = (float)($item["costPrice"] ?? $item["cost_price"] ?? $itemTypeCosts[$materialName] ?? 0);
                    $profitAmount = ($unitPrice - $costPrice) * $quantity;

                    DB::table("retail_sale_items")->insert([
                        "sale_id"      => $saleId,
                        "material"     => $materialName,
                        "quantity"     => $quantity,
                        "unit_price"   => $unitPrice,
                        "cost_price"   => $costPrice,
                        "profit_amount"=> $profitAmount,
                        "total_price"  => (float)($item["saleTotal"] ?? $unitPrice * $quantity),
                        "created_at"   => now(),
                        "updated_at"   => now()
                    ]);
                }
            }
        }

        $sale = DB::table("retail_sales")->find($saleId);
        $sale->items        = DB::table("retail_sale_items")->where("sale_id", $saleId)->get();
        $sale->client_name  = $clientName;
        $sale->client_phone = $clientPhone;
        $sale->payments     = [];
        return response()->json(["status" => "success", "sale_id" => $saleId, "store_id" => $storeId, "data" => $sale]);
    }

    private function mapStatus($status)
    {
        $map = ['Paid' => 'full', 'Debt' => 'debt', 'Partial' => 'partial', 'full' => 'full', 'debt' => 'debt', 'partial' => 'partial'];
        return $map[$status] ?? 'partial';
    }

    public function pay(Request $request, $saleId)
    {
        $user = $request->user();
        $data = $request->json()->all();
        $amount = floatval($data['amount'] ?? 0);
        $note   = $data['note'] ?? '';

        if ($amount <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Invalid amount'], 422);
        }

        $sale = DB::table('retail_sales')->find($saleId);
        if (!$sale) {
            return response()->json(['status' => 'error', 'message' => 'Sale not found'], 404);
        }

        // Cashiers can only pay debts for their own store
        if ($user->role === 'cashier' && $user->store_id && (int)$user->store_id !== (int)$sale->store_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $newPaid      = floatval($sale->paid_amount) + $amount;
        $newRemaining = max(floatval($sale->total_amount) - $newPaid, 0);
        $newStatus    = $newRemaining == 0 ? 'full' : 'partial';

        $payments   = json_decode($sale->payments ?? '[]', true) ?: [];
        $payments[] = ['date' => now()->toDateString(), 'amount' => $amount, 'note' => $note];

        DB::table('retail_sales')->where('id', $saleId)->update([
            'paid_amount'      => $newPaid,
            'remaining_amount' => $newRemaining,
            'payment_status'   => $newStatus,
            'payments'         => json_encode($payments),
            'updated_at'       => now(),
        ]);

        DB::table('retail_payments')->insert([
            'sale_id'    => $saleId,
            'amount'     => $amount,
            'notes'      => $note ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'paid' => $newPaid, 'remaining' => $newRemaining, 'payment_status' => $newStatus]);
    }

    public function destroy(Request $request, $saleId)
    {
        $user = $request->user();
        if ($user->role === 'cashier') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $sale = DB::table('retail_sales')->find($saleId);
        if (!$sale) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        DB::table('retail_sale_items')->where('sale_id', $saleId)->delete();
        DB::table('retail_payments')->where('sale_id', $saleId)->delete();
        DB::table('retail_sales')->where('id', $saleId)->delete();
        return response()->json(['status' => 'success']);
    }

    public function index(Request $request, $storeId)
    {
        $user = $request->user();

        // Cashiers can only see their own store
        if ($user->role === 'cashier' && $user->store_id && (int)$user->store_id !== (int)$storeId) {
            return response()->json(["status" => "error", "message" => "Unauthorized"], 403);
        }

        $sales = DB::table("retail_sales")
            ->where("store_id", $storeId)
            ->orderBy("sale_date", "desc")
            ->orderBy("created_at", "desc")
            ->get();

        foreach ($sales as $sale) {
            $sale->items       = DB::table("retail_sale_items")->where("sale_id", $sale->id)->get();
            $client            = DB::table("retail_clients")->find($sale->client_id);
            $sale->client_name = $client ? $client->name : "One-time customer";
            $sale->client_phone = $client ? $client->phone : "-";
            $sale->payments    = json_decode($sale->payments ?? '[]', true) ?: [];
        }

        return response()->json(["status" => "success", "data" => $sales]);
    }
}
