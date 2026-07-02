<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable()->default(1)->after('id');
            $table->index('store_id', 'inventory_sales_store_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_sales', function (Blueprint $table) {
            $table->dropIndex('inventory_sales_store_id_idx');
            $table->dropColumn('store_id');
        });
    }
};
