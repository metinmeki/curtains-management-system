<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RetailSaleItem extends Model
{
    use HasFactory;

    protected $fillable = ['sale_id', 'material', 'quantity', 'unit_price', 'total_price'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(RetailSale::class, 'sale_id');
    }
}
