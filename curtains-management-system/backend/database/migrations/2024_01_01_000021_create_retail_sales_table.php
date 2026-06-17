<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('retail_client_id')->nullable()->constrained()->nullOnDelete();
            // For one-time clients not saved in clients table
            $table->string('client_name')->nullable();
            $table->string('client_phone')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_note')->nullable();
            $table->decimal('final_amount', 12, 2); // total_amount - discount_amount
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['paid', 'partial', 'debt'])->default('paid');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_sales');
    }
};
