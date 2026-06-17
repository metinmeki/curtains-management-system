<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventorySale extends Model
{
    protected $fillable = [
        'inventory_client_id', 'total_amount', 'paid_amount',
        'remaining_amount', 'payment_status', 'notes', 'created_by',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'paid_amount' => 'float',
        'remaining_amount' => 'float',
    ];

    public function client()
    {
        return $this->belongsTo(InventoryClient::class, 'inventory_client_id');
    }

    public function items()
    {
        return $this->hasMany(InventorySaleItem::class);
    }

    public function debt()
    {
        return $this->hasOne(InventoryDebt::class);
    }
}
