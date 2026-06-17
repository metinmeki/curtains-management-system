<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailExpense extends Model
{
    protected $fillable = ['store_id', 'category', 'amount', 'note', 'expense_date', 'created_by'];

    protected $casts = [
        'amount' => 'float',
        'expense_date' => 'date',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
