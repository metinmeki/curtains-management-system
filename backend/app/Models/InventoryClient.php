<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryClient extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'notes', 'balance'];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function inventorySales()
    {
        return $this->hasMany(InventorySale::class, 'client_id');
    }
}
