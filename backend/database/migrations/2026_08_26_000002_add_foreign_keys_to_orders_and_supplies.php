<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support adding FK constraints to existing tables via ALTER TABLE
        if (DB::getDriverName() === 'sqlite') return;

        Schema::table('retail_orders', function (Blueprint $table) {
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });

        Schema::table('warehouse_supplies', function (Blueprint $table) {
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });

        Schema::table('warehouse_supply_items', function (Blueprint $table) {
            $table->foreign('supply_id')->references('id')->on('warehouse_supplies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        Schema::table('warehouse_supply_items', function (Blueprint $table) {
            $table->dropForeign(['supply_id']);
        });

        Schema::table('warehouse_supplies', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
        });

        Schema::table('retail_orders', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
        });
    }
};
