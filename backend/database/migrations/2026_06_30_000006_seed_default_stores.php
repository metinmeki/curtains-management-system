<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('stores')->count() === 0) {
            DB::table('stores')->insert([
                ['id' => 1, 'name' => 'Store 1', 'created_at' => now(), 'updated_at' => now()],
                ['id' => 2, 'name' => 'Store 2', 'created_at' => now(), 'updated_at' => now()],
                ['id' => 3, 'name' => 'Store 3', 'created_at' => now(), 'updated_at' => now()],
                ['id' => 4, 'name' => 'Store 4', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void {}
};
