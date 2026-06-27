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

        // Store ID comes from the authenticated user — never from frontend input
        $storeId = $user->store_id ?? ($data["storeId"] ?? 1);

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
            "total_amount"    => $data["total"] ?? 0,
            "paid_amount"     => $data["paid"] ?? 0,
            "remaining_amount"=> $data["remaining"] ?? 0,
            "payment_status"  => $data["status"] ?? "partial",
            "discount_amount" => $data["discount"] ?? 0,
            "discount_note"   => $data["discountNote"] ?? null,
            "notes"           => $data["note"] ?? null,
            "created_by"      => $user->name,
            "created_at"      => now(),
            "updated_at"      => now()
        ]);

        if (!empty($data["items"])) {
            foreach ($data["items"] as $item) {
                if (($item["quantity"] ?? 0) > 0 && ($item["price"] ?? 0) > 0) {
                    DB::table("retail_sale_items")->insert([
                        "sale_id"    => $saleId,
                        "material"   => $item["material"] ?? "Unknown",
                        "quantity"   => $item["quantity"] ?? 0,
                        "unit_price" => $item["price"] ?? 0,
                        "total_price"=> $item["saleTotal"] ?? 0,
                        "created_at" => now(),
                        "updated_at" => now()
                    ]);
                }
            }
        }

        return response()->json(["status" => "success", "sale_id" => $saleId, "store_id" => $storeId]);
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
            ->orderBy("created_at", "desc")
            ->get();

        foreach ($sales as $sale) {
            $sale->items       = DB::table("retail_sale_items")->where("sale_id", $sale->id)->get();
            $client            = DB::table("retail_clients")->find($sale->client_id);
            $sale->client_name = $client ? $client->name : "One-time customer";
        }

        return response()->json(["status" => "success", "data" => $sales]);
    }
}
