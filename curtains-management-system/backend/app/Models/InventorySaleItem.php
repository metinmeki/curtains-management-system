<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventorySaleItem extends Model
{
    protected $fillable = [
        'inventory_sale_id', 'inventory_category_id', 'inventory_product_type_id',
        'quantity', 'unit_price', 'total_price', 'note',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'total_price' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(InventorySale::class, 'inventory_sale_id');
    }

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function productType()
    {
        return $this->belongsTo(InventoryProductType::class, 'inventory_product_type_id');
    }
}
