<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('store_transfers')->orderByDesc('created_at');

        if ($request->has('store_id')) {
            $storeId = $request->store_id;
            $query->where(function ($q) use ($storeId) {
                $q->where('from_store_id', $storeId)->orWhere('to_store_id', $storeId);
            });
        }

        $transfers = $query->get()->map(function ($t) {
            $t->items = json_decode($t->items, true) ?? [];
            return $t;
        });

        return response()->json(['status' => 'success', 'data' => $transfers]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_store_id'       => 'required|integer',
            'to_store_id'         => 'required|integer|different:from_store_id',
            'items'               => 'required|array|min:1',
            'items.*.variantId'   => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.001',
        ]);

        $fromId  = $request->from_store_id;
        $toId    = $request->to_store_id;
        $items   = $request->items;

        // Load source stock
        $srcRow    = DB::table('retail_kv')->where('store_id', $fromId)->where('key', 'stock')->first();
        $srcStock  = $srcRow ? (json_decode($srcRow->value, true) ?? []) : [];

        // Load destination stock
        $dstRow    = DB::table('retail_kv')->where('store_id', $toId)->where('key', 'stock')->first();
        $dstStock  = $dstRow ? (json_decode($dstRow->value, true) ?? []) : [];

        // Validate sufficient stock for every item
        foreach ($items as $item) {
            $vid       = $item['variantId'];
            $qty       = floatval($item['quantity']);
            $available = floatval($srcStock[$vid] ?? 0);
            if ($available < $qty) {
                $code = $item['variantCode'] ?? $vid;
                return response()->json([
                    'status'  => 'error',
                    'message' => "الكمية غير كافية للصنف {$code}. المتاح: {$available}",
                ], 422);
            }
        }

        // Apply stock changes
        foreach ($items as $item) {
            $vid           = $item['variantId'];
            $qty           = floatval($item['quantity']);
            $srcStock[$vid] = round(floatval($srcStock[$vid] ?? 0) - $qty, 4);
            $dstStock[$vid] = round(floatval($dstStock[$vid] ?? 0) + $qty, 4);
        }

        DB::beginTransaction();
        try {
            $now = now();

            DB::table('retail_kv')->updateOrInsert(
                ['store_id' => $fromId, 'key' => 'stock'],
                ['value' => json_encode($srcStock), 'updated_at' => $now, 'created_at' => $now]
            );

            DB::table('retail_kv')->updateOrInsert(
                ['store_id' => $toId, 'key' => 'stock'],
                ['value' => json_encode($dstStock), 'updated_at' => $now, 'created_at' => $now]
            );

            // Optionally copy variant/item/category definitions to destination
            $patch = $request->dest_variants_patch ?? null;
            if ($patch) {
                $this->mergeKvArray($toId, 'categories', $patch['categories'] ?? [], $now);
                $this->mergeKvArray($toId, 'items',      $patch['items']      ?? [], $now);
                $this->mergeKvArray($toId, 'variants',   $patch['variants']   ?? [], $now);
            }

            $ref = 'TRF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $id = DB::table('store_transfers')->insertGetId([
                'from_store_id' => $fromId,
                'to_store_id'   => $toId,
                'date'          => $request->date ?? date('Y-m-d'),
                'reference'     => $ref,
                'items'         => json_encode($items),
                'notes'         => $request->notes ?? null,
                'status'        => 'completed',
                'created_by'    => auth()->user()->name ?? null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'id' => $id, 'reference' => $ref]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function mergeKvArray($storeId, $key, array $incoming, $now)
    {
        if (empty($incoming)) return;

        $row      = DB::table('retail_kv')->where('store_id', $storeId)->where('key', $key)->first();
        $existing = $row ? (json_decode($row->value, true) ?? []) : [];

        $existingIds = array_column($existing, 'id');
        foreach ($incoming as $entry) {
            if (!in_array($entry['id'] ?? null, $existingIds)) {
                $existing[] = $entry;
            }
        }

        DB::table('retail_kv')->updateOrInsert(
            ['store_id' => $storeId, 'key' => $key],
            ['value' => json_encode(array_values($existing)), 'updated_at' => $now, 'created_at' => $now]
        );
    }
}
