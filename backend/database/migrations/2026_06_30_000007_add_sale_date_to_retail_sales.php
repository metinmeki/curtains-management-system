<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retail_sales', function (Blueprint $table) {
            $table->date('sale_date')->nullable()->after('store_id');
        });
        // Backfill existing rows from created_at
        DB::statement("UPDATE retail_sales SET sale_date = date(created_at) WHERE sale_date IS NULL");
    }

    public function down(): void
    {
        Schema::table('retail_sales', function (Blueprint $table) {
            $table->dropColumn('sale_date');
        });
    }
};
