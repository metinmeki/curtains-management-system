<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryProductType extends Model
{
    protected $fillable = ['inventory_category_id', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }
}
