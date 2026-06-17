<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryProductType;
use Illuminate\Http\Request;

class InventoryCategoryController extends Controller
{
    public function index()
    {
        return response()->json(InventoryCategory::where('is_active', true)->with('productTypes')->get());
    }

    public function types(int $category)
    {
        $types = InventoryProductType::where('inventory_category_id', $category)
            ->where('is_active', true)
            ->get();

        return response()->json($types);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|unique:inventory_categories,name']);
        return response()->json(InventoryCategory::create($data), 201);
    }

    public function storeType(Request $request, int $category)
    {
        $data = $request->validate(['name' => 'required|string']);
        $type = InventoryProductType::create([
            'inventory_category_id' => $category,
            'name' => $data['name'],
        ]);
        return response()->json($type, 201);
    }
}
