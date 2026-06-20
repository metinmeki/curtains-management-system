<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryPayment extends Model
{
    use HasFactory;

    protected $fillable = ['sale_id', 'amount', 'notes'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(InventorySale::class, 'sale_id');
    }
}
