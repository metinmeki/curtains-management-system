<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryDebt extends Model
{
    protected $fillable = [
        'inventory_sale_id', 'inventory_client_id',
        'original_amount', 'paid_amount', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'original_amount' => 'float',
        'paid_amount' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(InventorySale::class, 'inventory_sale_id');
    }

    public function client()
    {
        return $this->belongsTo(InventoryClient::class, 'inventory_client_id');
    }

    public function payments()
    {
        return $this->hasMany(InventoryDebtPayment::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        $totalPaid = $this->payments()->sum('amount');
        return max(0, $this->original_amount - $totalPaid);
    }

    public function syncPaidAmountAndStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->paid_amount = $totalPaid;

        if ($totalPaid <= 0) {
            $this->status = 'open';
        } elseif ($totalPaid >= $this->original_amount) {
            $this->status = 'paid';
        } else {
            $this->status = 'partial';
        }

        $this->save();
    }
}
