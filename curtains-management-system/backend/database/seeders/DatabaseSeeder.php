<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Owner account
        User::create([
            'name'     => 'Owner',
            'email'    => 'owner@curtains.com',
            'password' => Hash::make('password'),
            'role'     => 'owner',
        ]);

        // Four retail stores
        foreach (['المحل 1', 'المحل 2', 'المحل 3', 'المحل 4'] as $name) {
            Store::create(['name' => $name, 'type' => 'retail']);
        }

        // Inventory unit
        Store::create(['name' => 'المخزن', 'type' => 'inventory']);

        // Inventory product categories as defined in proposal
        $categories = ['شريط', 'سكة', 'حبل', 'اويه', 'بطن', 'بوري', 'رولة'];
        foreach ($categories as $cat) {
            InventoryCategory::create(['name' => $cat]);
        }
    }
}
