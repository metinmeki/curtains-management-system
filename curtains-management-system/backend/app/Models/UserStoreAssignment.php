<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStoreAssignment extends Model
{
    protected $fillable = ['user_id', 'store_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
