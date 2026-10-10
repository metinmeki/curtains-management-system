<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Clears trading history so a store can start a clean period.
 *
 * Deliberately narrow: transactions go, configuration stays. Stores, users,
 * clients, inventory items and stock levels survive, so the shop is still set
 * up and open the moment the reset finishes. Wiping those too would turn a
 * routine end-of-period clear into a rebuild.
 */
class SystemResetController extends Controller
{
    /** Tables cleared, in FK-safe order. Items and payments cascade from sales. */
    private const CLEARS = [
        'retail_sales'     => 'store_id',
        'retail_expenses'  => 'store_id',
        'retail_orders'    => 'store_id',
    ];

    /**
     * Counts what a reset would remove, without removing it. The confirmation
     * screen shows real numbers rather than asking the admin to delete blind.
     */
    public function preview(Request $request)
    {
        if ($denied = $this->denyNonAdmin($request)) return $denied;

        $storeId = $request->query('store_id');
        return response()->json([
            'status' => 'success',
            'scope'  => $storeId ? "store {$storeId}" : 'all stores',
            'data'   => $this->counts($storeId),
        ]);
    }

    public function reset(Request $request)
    {
        if ($denied = $this->denyNonAdmin($request)) return $denied;

        $data = $request->json()->all();
        $storeId = isset($data['store_id']) && $data['store_id'] !== '' ? (int) $data['store_id'] : null;

        // Typed confirmation. Without it a stray POST from a cached tab or a
        // mis-click in an API client would be enough to erase a year of trading.
        if (($data['confirm'] ?? '') !== 'RESET') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Confirmation phrase missing. Send confirm: "RESET".',
            ], 422);
        }

        if ($storeId !== null && !DB::table('stores')->where('id', $storeId)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Unknown store.'], 404);
        }

        $before = $this->counts($storeId);

        DB::transaction(function () use ($storeId) {
            foreach (self::CLEARS as $table => $column) {
                $q = DB::table($table);
                if ($storeId !== null) $q->where($column, $storeId);
                $q->delete();
            }

            // Transfers reference two stores, so a single-store reset only drops
            // the ones that store took part in.
            $t = DB::table('store_transfers');
            if ($storeId !== null) {
                $t->where('from_store_id', $storeId)->orWhere('to_store_id', $storeId);
            }
            $t->delete();

            // Orphans are possible if a sale was deleted directly in the past,
            // so sweep rows whose parent sale no longer exists.
            DB::table('retail_sale_items')->whereNotIn('sale_id', DB::table('retail_sales')->select('id'))->delete();
            DB::table('retail_payments')->whereNotIn('sale_id', DB::table('retail_sales')->select('id'))->delete();
        });

        return response()->json([
            'status'  => 'success',
            'scope'   => $storeId ? "store {$storeId}" : 'all stores',
            'cleared' => $before,
            'data'    => $this->counts($storeId),
        ]);
    }

    private function counts($storeId): array
    {
        $scoped = function ($table, $column) use ($storeId) {
            $q = DB::table($table);
            if ($storeId !== null) $q->where($column, $storeId);
            return $q->count();
        };

        $transfers = DB::table('store_transfers');
        if ($storeId !== null) {
            $transfers->where('from_store_id', $storeId)->orWhere('to_store_id', $storeId);
        }

        return [
            'sales'     => $scoped('retail_sales', 'store_id'),
            'expenses'  => $scoped('retail_expenses', 'store_id'),
            'orders'    => $scoped('retail_orders', 'store_id'),
            'transfers' => $transfers->count(),
        ];
    }

    private function denyNonAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only an admin can reset system data.',
            ], 403);
        }
        return null;
    }
}
