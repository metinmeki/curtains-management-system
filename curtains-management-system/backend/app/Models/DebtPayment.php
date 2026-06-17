<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtPayment extends Model
{
    protected $fillable = ['retail_debt_id', 'amount', 'notes', 'created_by'];

    protected $casts = ['amount' => 'float'];

    public function debt()
    {
        return $this->belongsTo(RetailDebt::class, 'retail_debt_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
