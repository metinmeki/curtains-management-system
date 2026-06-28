<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('reference')->unique();
            $table->string('buyer_name')->default('Order customer');
            $table->string('buyer_phone')->nullable();
            $table->string('supplier_name')->nullable();
            $table->text('notes')->nullable();
            $table->date('date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('delivery_price', 15, 2)->default(0);
            $table->decimal('final_total', 15, 2)->default(0);
            $table->decimal('buyer_paid', 15, 2)->default(0);
            $table->decimal('buyer_due', 15, 2)->default(0);
            $table->string('buyer_status')->default('debt');
            $table->decimal('our_profit', 15, 2)->default(0);
            $table->decimal('supplier_share', 15, 2)->default(0);
            $table->decimal('supplier_paid', 15, 2)->default(0);
            $table->decimal('supplier_due', 15, 2)->default(0);
            $table->string('supplier_status')->default('debt');
            $table->text('items')->nullable();
            $table->text('supplier_payments')->nullable();
            $table->text('buyer_payments')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_orders');
    }
};
