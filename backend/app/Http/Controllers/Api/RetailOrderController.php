<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RetailOrderController extends Controller
{
    public function store(Request $request)
    {
        $user    = $request->user();
        $data    = $request->json()->all();
        $storeId = $user->store_id ?? ($data['storeId'] ?? 1);

        // Avoid duplicate saves
        if (!empty($data['reference']) && DB::table('retail_orders')->where('reference', $data['reference'])->exists()) {
            $order = DB::table('retail_orders')->where('reference', $data['reference'])->first();
            return response()->json(['status' => 'success', 'id' => $order->id]);
        }

        $id = DB::table('retail_orders')->insertGetId([
            'store_id'          => $storeId,
            'reference'         => $data['reference'] ?? ('ORD-' . time()),
            'buyer_name'        => $data['buyerName'] ?? 'Order customer',
            'buyer_phone'       => $data['buyerPhone'] ?? null,
            'supplier_name'     => $data['supplierName'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'date'              => $data['date'] ?? now()->toDateString(),
            'subtotal'          => $data['subtotal'] ?? 0,
            'delivery_price'    => $data['deliveryPrice'] ?? 0,
            'final_total'       => $data['finalTotal'] ?? 0,
            'buyer_paid'        => $data['buyerPaid'] ?? 0,
            'buyer_due'         => $data['buyerDue'] ?? 0,
            'buyer_status'      => $data['buyerStatus'] ?? 'debt',
            'our_profit'        => $data['ourProfit'] ?? 0,
            'supplier_share'    => $data['supplierShare'] ?? 0,
            'supplier_paid'     => $data['supplierPaid'] ?? 0,
            'supplier_due'      => $data['supplierDue'] ?? 0,
            'supplier_status'   => $data['supplierStatus'] ?? 'debt',
            'items'             => json_encode($data['items'] ?? []),
            'supplier_payments' => json_encode($data['supplierPayments'] ?? []),
            'buyer_payments'    => json_encode($data['buyerPayments'] ?? []),
            'created_by'        => $user->name,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return response()->json(['status' => 'success', 'id' => $id]);
    }

    public function index(Request $request, $storeId)
    {
        $user = $request->user();
        if ($user->role === 'cashier' && $user->store_id && (int)$user->store_id !== (int)$storeId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $orders = DB::table('retail_orders')->where('store_id', $storeId)->orderBy('date', 'desc')->get();
        foreach ($orders as $order) {
            $order->items             = json_decode($order->items ?? '[]', true) ?: [];
            $order->supplier_payments = json_decode($order->supplier_payments ?? '[]', true) ?: [];
            $order->buyer_payments    = json_decode($order->buyer_payments ?? '[]', true) ?: [];
        }

        return response()->json(['status' => 'success', 'data' => $orders]);
    }
}
