<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A refund reduces total_amount and paid_amount in place, so without this
     * column the original sale value is lost and there is no way to tell a
     * refunded sale from one that was simply smaller. Keeping the running
     * refunded total alongside preserves that history.
     */
    public function up(): void
    {
        Schema::table('retail_sales', function (Blueprint $table) {
            $table->decimal('refunded_amount', 14, 2)->default(0)->after('remaining_amount');
        });

        // Refunds are stored as negative rows in retail_payments so the money
        // ledger stays a single ordered list; the flag makes them queryable.
        Schema::table('retail_payments', function (Blueprint $table) {
            $table->boolean('is_refund')->default(false)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('retail_sales', function (Blueprint $table) {
            $table->dropColumn('refunded_amount');
        });
        Schema::table('retail_payments', function (Blueprint $table) {
            $table->dropColumn('is_refund');
        });
    }
};
