<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailSaleItem extends Model
{
    protected $fillable = ['retail_sale_id', 'material', 'quantity', 'unit_price', 'total_price', 'note'];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'total_price' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(RetailSale::class, 'retail_sale_id');
    }
}
