<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('retail_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('retail_client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_name');
            $table->string('client_phone')->nullable();
            $table->decimal('original_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            // remaining is always computed: original_amount - SUM(payments)
            $table->enum('status', ['open', 'partial', 'paid'])->default('open');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_debts');
    }
};
