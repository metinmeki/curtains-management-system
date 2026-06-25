<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryCategoryController extends Controller
{
    public function index()
    {
        $categories = DB::table('categories')->get();
        foreach($categories as $category) {
            $category->sub_categories = DB::table('sub_categories')->where('category_id', $category->id)->get();
        }
        return response()->json(['status' => 'success', 'data' => $categories]);
    }

    public function storeMainCategory(Request $request)
    {
        DB::table('categories')->insert(['name' => $request->input('name')]);
        return response()->json(['status' => 'success']);
    }

    public function storeSubCategory(Request $request)
    {
        DB::table('sub_categories')->insert([
            'category_id' => $request->input('category_id'),
            'name' => $request->input('name')
        ]);
        return response()->json(['status' => 'success']);
    }
}
