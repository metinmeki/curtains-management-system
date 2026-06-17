<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryClient;
use Illuminate\Http\Request;

class InventoryClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = InventoryClient::when($request->search, fn($q) =>
            $q->where('name', 'like', "%{$request->search}%")
              ->orWhere('phone', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20);

        return response()->json($clients);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $client = InventoryClient::create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($client, 201);
    }

    public function show(int $client)
    {
        $client = InventoryClient::with([
            'sales' => fn($q) => $q->with('items.category', 'items.productType')->latest()->limit(20),
            'debts' => fn($q) => $q->with('payments')->whereIn('status', ['open', 'partial']),
        ])->findOrFail($client);

        return response()->json([
            'client'      => $client,
            'total_sales' => $client->total_sales,
            'total_debt'  => $client->total_debt,
            'total_paid'  => $client->total_paid,
        ]);
    }

    public function update(Request $request, int $client)
    {
        $clientModel = InventoryClient::findOrFail($client);

        $data = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $clientModel->update($data);

        return response()->json($clientModel);
    }
}
