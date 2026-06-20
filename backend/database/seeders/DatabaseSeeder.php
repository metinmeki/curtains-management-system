<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Store::create(['name' => 'Store 1']);
        Store::create(['name' => 'Store 2']);
        Store::create(['name' => 'Store 3']);
        Store::create(['name' => 'Store 4']);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@curtains.com',
            'password' => Hash::make('admin123'),
        ]);
    }
}
