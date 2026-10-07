<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The POS captures far more than was being stored: per-line fabric codes,
     * units and notes were dropped, and a discount lost whether it was a
     * percentage or a flat amount. Without these a past sale can't be
     * reconstructed, which the client details view needs.
     */
    public function up(): void
    {
        Schema::table('retail_sales', function (Blueprint $table) {
            $table->decimal('subtotal', 14, 2)->nullable()->after('total_amount');
            $table->string('discount_type', 10)->nullable()->after('discount_amount');
            // What the cashier typed prices in, plus the rate at that moment, so
            // changing the rate later can't silently restate historic sales.
            $table->string('price_currency', 3)->nullable()->after('discount_type');
            $table->decimal('exchange_rate', 12, 2)->nullable()->after('price_currency');
        });

        Schema::table('retail_sale_items', function (Blueprint $table) {
            $table->string('code')->nullable()->after('material');
            $table->unsignedBigInteger('variant_id')->nullable()->after('code');
            $table->string('variant_name')->nullable()->after('variant_id');
            $table->string('unit', 20)->nullable()->after('variant_name');
            $table->text('note')->nullable()->after('unit');
            $table->decimal('account_total', 14, 2)->nullable()->after('profit_amount');
            $table->decimal('store_share', 14, 2)->nullable()->after('account_total');
        });
    }

    public function down(): void
    {
        Schema::table('retail_sales', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount_type', 'price_currency', 'exchange_rate']);
        });

        Schema::table('retail_sale_items', function (Blueprint $table) {
            $table->dropColumn(['code', 'variant_id', 'variant_name', 'unit', 'note', 'account_total', 'store_share']);
        });
    }
};
