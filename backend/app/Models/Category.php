<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class);
    }

    public function inventorySaleItems()
    {
        return $this->hasMany(InventorySaleItem::class);
    }
}
