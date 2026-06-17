<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailClient extends Model
{
    protected $fillable = ['store_id', 'name', 'phone', 'notes', 'last_transaction_at', 'created_by'];

    protected $casts = ['last_transaction_at' => 'datetime'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function sales()
    {
        return $this->hasMany(RetailSale::class);
    }

    public function debts()
    {
        return $this->hasMany(RetailDebt::class);
    }

    public function getTotalSalesAttribute(): float
    {
        return $this->sales()->sum('final_amount');
    }

    public function getTotalDebtAttribute(): float
    {
        return $this->debts()->whereIn('status', ['open', 'partial'])->sum('original_amount')
            - $this->debts()->whereIn('status', ['open', 'partial'])->sum('paid_amount');
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->sales()->sum('paid_amount');
    }
}
