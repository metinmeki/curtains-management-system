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
        $request->validate([
            'finalTotal'   => 'sometimes|numeric|min:0',
            'buyerPaid'    => 'sometimes|numeric|min:0',
            'supplierShare'=> 'sometimes|numeric|min:0',
            'supplierPaid' => 'sometimes|numeric|min:0',
            'ourProfit'    => 'sometimes|numeric|min:0',
            'deliveryPrice'=> 'sometimes|numeric|min:0',
        ]);

        $storeId = ($user->role === 'cashier') ? ($user->store_id ?? 1) : ($data['storeId'] ?? $user->store_id ?? 1);

        // Avoid duplicate saves (try-catch handles the race condition where two
        // requests pass the exists() check before either inserts)
        if (!empty($data['reference'])) {
            $existing = DB::table('retail_orders')->where('reference', $data['reference'])->first();
            if ($existing) return response()->json(['status' => 'success', 'id' => $existing->id]);
        }

        try {
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
            'approval_status'   => $data['approvalStatus'] ?? 'accepted',
            'items'             => json_encode($data['items'] ?? []),
            'supplier_payments' => json_encode($data['supplierPayments'] ?? []),
            'buyer_payments'    => json_encode($data['buyerPayments'] ?? []),
            'created_by'        => $user->name,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $order = DB::table('retail_orders')->where('reference', $data['reference'] ?? '')->first();
            return response()->json(['status' => 'success', 'id' => $order ? $order->id : null]);
        }

        return response()->json(['status' => 'success', 'id' => $id]);
    }

    public function pay(Request $request, $orderId)
    {
        $data   = $request->json()->all();
        $party  = $data['party'] ?? 'buyer'; // 'buyer' or 'supplier'
        $amount = floatval($data['amount'] ?? 0);
        $note   = $data['note'] ?? '';

        if ($amount <= 0) return response()->json(['status' => 'error', 'message' => 'Invalid amount'], 422);

        $order = DB::table('retail_orders')->find($orderId);
        if (!$order) return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);

        if ($party === 'supplier') {
            $newPaid = floatval($order->supplier_paid) + $amount;
            $newDue  = max(floatval($order->supplier_share) - $newPaid, 0);
            $payments = json_decode($order->supplier_payments ?? '[]', true) ?: [];
            $payments[] = ['date' => now()->toDateString(), 'amount' => $amount, 'note' => $note];
            DB::table('retail_orders')->where('id', $orderId)->update([
                'supplier_paid'     => $newPaid,
                'supplier_due'      => $newDue,
                'supplier_status'   => $newDue <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'debt'),
                'supplier_payments' => json_encode($payments),
                'updated_at'        => now(),
            ]);
        } else {
            $newPaid = floatval($order->buyer_paid) + $amount;
            $newDue  = max(floatval($order->final_total) - $newPaid, 0);
            $payments = json_decode($order->buyer_payments ?? '[]', true) ?: [];
            $payments[] = ['date' => now()->toDateString(), 'amount' => $amount, 'note' => $note];
            DB::table('retail_orders')->where('id', $orderId)->update([
                'buyer_paid'     => $newPaid,
                'buyer_due'      => $newDue,
                'buyer_status'   => $newDue <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'debt'),
                'buyer_payments' => json_encode($payments),
                'updated_at'     => now(),
            ]);
        }

        $updated = DB::table('retail_orders')->find($orderId);
        return response()->json(['status' => 'success', 'data' => $updated]);
    }

    public function accept(Request $request, $orderId)
    {
        $order = DB::table('retail_orders')->find($orderId);
        if (!$order) return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);

        DB::table('retail_orders')->where('id', $orderId)->update([
            'approval_status' => 'accepted',
            'updated_at'      => now(),
        ]);

        $updated = DB::table('retail_orders')->find($orderId);
        return response()->json(['status' => 'success', 'data' => $updated]);
    }

    public function destroy(Request $request, $orderId)
    {
        $user = $request->user();
        if ($user->role === 'cashier') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $order = DB::table('retail_orders')->find($orderId);
        if (!$order) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        DB::table('retail_orders')->where('id', $orderId)->delete();
        return response()->json(['status' => 'success']);
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
