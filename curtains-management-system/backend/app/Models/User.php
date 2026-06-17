<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function storeAssignments()
    {
        return $this->hasMany(UserStoreAssignment::class);
    }

    public function assignedStores()
    {
        return $this->belongsToMany(Store::class, 'user_store_assignments');
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isInventoryUser(): bool
    {
        return $this->role === 'inventory_user';
    }

    public function canAccessStore(int $storeId): bool
    {
        if ($this->isOwner()) {
            return true;
        }
        return $this->storeAssignments()->where('store_id', $storeId)->exists();
    }
}
