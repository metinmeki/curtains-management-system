<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppSettingController extends Controller
{
    /**
     * System-wide settings. Readable by anyone signed in (cashiers included)
     * because money formatting depends on the exchange rate.
     */
    public function index()
    {
        $settings = DB::table('app_settings')->pluck('value', 'key');
        return response()->json(['status' => 'success', 'data' => $settings]);
    }

    /**
     * Admin-only. A cashier changing the exchange rate would silently rewrite
     * every displayed total, so the write side is gated on role.
     */
    public function update(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only an admin can change system settings.',
            ], 403);
        }

        $validated = $request->validate([
            'usd_to_iqd_rate' => 'required|numeric|min:1|max:1000000',
        ]);

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'usd_to_iqd_rate'],
            ['value' => (string) $validated['usd_to_iqd_rate'], 'updated_at' => now(), 'created_at' => now()]
        );

        return response()->json([
            'status' => 'success',
            'data'   => DB::table('app_settings')->pluck('value', 'key'),
        ]);
    }
}
