<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailSale extends Model
{
    protected $fillable = [
        'store_id', 'retail_client_id', 'client_name', 'client_phone',
        'total_amount', 'discount_amount', 'discount_note', 'final_amount',
        'paid_amount', 'remaining_amount', 'payment_status', 'notes', 'created_by',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'discount_amount' => 'float',
        'final_amount' => 'float',
        'paid_amount' => 'float',
        'remaining_amount' => 'float',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function client()
    {
        return $this->belongsTo(RetailClient::class, 'retail_client_id');
    }

    public function items()
    {
        return $this->hasMany(RetailSaleItem::class);
    }

    public function debt()
    {
        return $this->hasOne(RetailDebt::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
