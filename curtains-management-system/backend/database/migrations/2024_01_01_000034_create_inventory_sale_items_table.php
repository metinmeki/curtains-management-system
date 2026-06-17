<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_category_id')->constrained();
            $table->foreignId('inventory_product_type_id')->constrained();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_sale_items');
    }
};
