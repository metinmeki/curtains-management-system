<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryDebtPayment extends Model
{
    protected $fillable = ['inventory_debt_id', 'amount', 'notes', 'created_by'];

    protected $casts = ['amount' => 'float'];

    public function debt()
    {
        return $this->belongsTo(InventoryDebt::class, 'inventory_debt_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
