<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_supplies', function (Blueprint $table) {
            $table->index('store_id', 'warehouse_supplies_store_id_idx');
        });

        Schema::table('warehouse_supply_items', function (Blueprint $table) {
            $table->index('supply_id', 'warehouse_supply_items_supply_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_supplies', function (Blueprint $table) {
            $table->dropIndex('warehouse_supplies_store_id_idx');
        });

        Schema::table('warehouse_supply_items', function (Blueprint $table) {
            $table->dropIndex('warehouse_supply_items_supply_id_idx');
        });
    }
};
