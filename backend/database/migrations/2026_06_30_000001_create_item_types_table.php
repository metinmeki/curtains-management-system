<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->string('unit')->default('meter');
            $table->boolean('affects_sale_total')->default(true);
            $table->string('worker_account')->nullable(); // 'sewing' | 'installation' | null
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default item types
        $now = now();
        \Illuminate\Support\Facades\DB::table('item_types')->insert([
            ['code' => 'QMSH', 'name' => 'قماش',   'cost_price' => 0, 'selling_price' => 0, 'unit' => 'meter',  'affects_sale_total' => 1, 'worker_account' => null,           'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'TOUL', 'name' => 'تول',    'cost_price' => 0, 'selling_price' => 0, 'unit' => 'meter',  'affects_sale_total' => 1, 'worker_account' => null,           'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'BTAN', 'name' => 'بطانة',  'cost_price' => 0, 'selling_price' => 0, 'unit' => 'meter',  'affects_sale_total' => 1, 'worker_account' => null,           'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ZBRA', 'name' => 'زبرا',   'cost_price' => 0, 'selling_price' => 0, 'unit' => 'meter',  'affects_sale_total' => 1, 'worker_account' => null,           'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'KHYT', 'name' => 'خياطة',  'cost_price' => 0, 'selling_price' => 1000, 'unit' => 'meter',  'affects_sale_total' => 0, 'worker_account' => 'sewing',   'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'SKKA', 'name' => 'سكة',    'cost_price' => 0, 'selling_price' => 0, 'unit' => 'meter',  'affects_sale_total' => 1, 'worker_account' => null,           'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'TRKB', 'name' => 'تركيب',  'cost_price' => 0, 'selling_price' => 10000,'unit' => 'window', 'affects_sale_total' => 0, 'worker_account' => 'installation','is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'MZHR', 'name' => 'مزهرية', 'cost_price' => 0, 'selling_price' => 0, 'unit' => 'piece',  'affects_sale_total' => 1, 'worker_account' => null,           'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'HBAL', 'name' => 'حبال',   'cost_price' => 0, 'selling_price' => 0, 'unit' => 'piece',  'affects_sale_total' => 1, 'worker_account' => null,           'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('item_types');
    }
};
