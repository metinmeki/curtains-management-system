<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseClientController extends Controller
{
    public function index()
    {
        $clients = DB::table('inventory_clients')->orderBy('name')->get();
        foreach ($clients as $c) {
            $c->total_sales = (float)DB::table('inventory_sales')->where('client_id', $c->id)->sum('total_amount');
            $c->total_paid  = (float)DB::table('inventory_sales')->where('client_id', $c->id)->sum('paid_amount');
            $c->total_debt  = (float)DB::table('inventory_sales')->where('client_id', $c->id)->sum('remaining_amount');
            $c->last_date   = DB::table('inventory_sales')->where('client_id', $c->id)->max('created_at');
        }
        return response()->json(['status' => 'success', 'data' => $clients]);
    }

    public function store(Request $request)
    {
        $data = $request->json()->all();
        if (empty($data['name'])) return response()->json(['status' => 'error', 'message' => 'Name required'], 422);

        $id = DB::table('inventory_clients')->insertGetId([
            'name'       => $data['name'],
            'phone'      => $data['phone'] ?? null,
            'notes'      => $data['notes'] ?? null,
            'balance'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['status' => 'success', 'id' => $id]);
    }

    public function sales(Request $request, $clientId)
    {
        $sales = DB::table('inventory_sales')->where('client_id', $clientId)->orderBy('created_at', 'desc')->get();
        foreach ($sales as $s) {
            $s->items    = DB::table('inventory_sale_items')->where('sale_id', $s->id)->get();
            $s->payments = DB::table('inventory_payments')->where('sale_id', $s->id)->orderBy('created_at')->get();
        }
        return response()->json(['status' => 'success', 'data' => $sales]);
    }
}
