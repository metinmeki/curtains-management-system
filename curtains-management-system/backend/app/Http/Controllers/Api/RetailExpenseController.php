<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetailExpense;
use Illuminate\Http\Request;

class RetailExpenseController extends Controller
{
    // Expense categories as defined in proposal
    public static array $categories = [
        'الكهرب',
        'توصيل العامل',
        'ئوتي',
        'مالركت',
        'جاي قهوة',
        'الضيوف',
        'ئوسام',
        'دلوفان',
        'مصرف عمال',
    ];

    public function index(Request $request, int $store)
    {
        $expenses = RetailExpense::where('store_id', $store)
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->start_date, fn($q) => $q->whereDate('expense_date', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('expense_date', '<=', $request->end_date))
            ->latest('expense_date')
            ->paginate(20);

        return response()->json($expenses);
    }

    public function store(Request $request, int $store)
    {
        $data = $request->validate([
            'category'     => 'required|string',
            'amount'       => 'required|numeric|min:0.01',
            'note'         => 'nullable|string',
            'expense_date' => 'required|date',
        ]);

        $expense = RetailExpense::create([
            ...$data,
            'store_id'   => $store,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($expense, 201);
    }

    public function categories()
    {
        return response()->json(self::$categories);
    }
}
