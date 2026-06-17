<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = ['name', 'type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_store_assignments');
    }

    public function sales()
    {
        return $this->hasMany(RetailSale::class);
    }

    public function clients()
    {
        return $this->hasMany(RetailClient::class);
    }

    public function expenses()
    {
        return $this->hasMany(RetailExpense::class);
    }

    public function debts()
    {
        return $this->hasMany(RetailDebt::class);
    }
}
