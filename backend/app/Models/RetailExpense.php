<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RetailExpense extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'category', 'amount', 'note', 'date', 'created_by'];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
