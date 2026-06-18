<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventorySale extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(InventoryClient::class, 'client_id');
    }

    public function saleItems()
    {
        return $this->hasMany(InventorySaleItem::class, 'sale_id');
    }

    public function payments()
    {
        return $this->hasMany(InventoryPayment::class, 'sale_id');
    }
}
