<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RetailClient extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'name', 'phone', 'notes'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function retailSales()
    {
        return $this->hasMany(RetailSale::class, 'client_id');
    }
}
