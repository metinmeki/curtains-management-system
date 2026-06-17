<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            // Categories: الكهرب, توصيل العامل, ئوتي, مالركت, جاي قهوة, الضيوف, ئوسام, دلوفان, مصرف عمال
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->text('note')->nullable();
            $table->date('expense_date');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_expenses');
    }
};
