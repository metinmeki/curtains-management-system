<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailDebt extends Model
{
    protected $fillable = [
        'store_id', 'retail_sale_id', 'retail_client_id',
        'client_name', 'client_phone', 'original_amount',
        'paid_amount', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'original_amount' => 'float',
        'paid_amount' => 'float',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function sale()
    {
        return $this->belongsTo(RetailSale::class, 'retail_sale_id');
    }

    public function client()
    {
        return $this->belongsTo(RetailClient::class, 'retail_client_id');
    }

    public function payments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    // Always compute remaining from payment history, not a stored field
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
