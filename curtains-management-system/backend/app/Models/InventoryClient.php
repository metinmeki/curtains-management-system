<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryClient extends Model
{
    protected $fillable = ['name', 'phone', 'notes', 'balance', 'last_transaction_at', 'created_by'];

    protected $casts = [
        'balance' => 'float',
        'last_transaction_at' => 'datetime',
    ];

    public function sales()
    {
        return $this->hasMany(InventorySale::class);
    }

    public function debts()
    {
        return $this->hasMany(InventoryDebt::class);
    }

    public function getTotalSalesAttribute(): float
    {
        return $this->sales()->sum('total_amount');
    }

    public function getTotalDebtAttribute(): float
    {
        return $this->debts()->whereIn('status', ['open', 'partial'])
            ->selectRaw('SUM(original_amount - paid_amount) as remaining')
            ->value('remaining') ?? 0;
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->sales()->sum('paid_amount');
    }
}
