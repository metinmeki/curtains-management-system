<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_types', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->default(0)->after('selling_price');
        });
    }

    public function down(): void
    {
        Schema::table('item_types', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
