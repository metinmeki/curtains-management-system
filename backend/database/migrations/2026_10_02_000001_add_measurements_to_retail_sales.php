<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Curtain width and length, captured at the till so the fitters have the
     * measurements on the printed receipt when they come to install.
     */
    public function up(): void
    {
        Schema::table('retail_sales', function (Blueprint $table) {
            $table->decimal('sale_width', 10, 2)->nullable()->after('sale_details');
            $table->decimal('sale_length', 10, 2)->nullable()->after('sale_width');
        });
    }

    public function down(): void
    {
        Schema::table('retail_sales', function (Blueprint $table) {
            $table->dropColumn(['sale_width', 'sale_length']);
        });
    }
};
