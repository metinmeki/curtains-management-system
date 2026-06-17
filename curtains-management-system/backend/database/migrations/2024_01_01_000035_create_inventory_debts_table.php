<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_client_id')->constrained()->onDelete('cascade');
            $table->decimal('original_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->enum('status', ['open', 'partial', 'paid'])->default('open');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_debts');
    }
};
