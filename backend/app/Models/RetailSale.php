<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RetailSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'client_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'discount_amount',
        'discount_note',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function client()
    {
        return $this->belongsTo(RetailClient::class, 'client_id');
    }

    public function saleItems()
    {
        return $this->hasMany(RetailSaleItem::class, 'sale_id');
    }

    public function payments()
    {
        return $this->hasMany(RetailPayment::class, 'sale_id');
    }
}
