<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_supply_items', function (Blueprint $table) {
            $table->decimal('cost_price', 15, 2)->default(0)->after('price');
            $table->decimal('profit_amount', 15, 2)->default(0)->after('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_supply_items', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'profit_amount']);
        });
    }
};
