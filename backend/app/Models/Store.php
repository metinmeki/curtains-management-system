<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function retailClients()
    {
        return $this->hasMany(RetailClient::class);
    }

    public function retailSales()
    {
        return $this->hasMany(RetailSale::class);
    }

    public function retailExpenses()
    {
        return $this->hasMany(RetailExpense::class);
    }
}
