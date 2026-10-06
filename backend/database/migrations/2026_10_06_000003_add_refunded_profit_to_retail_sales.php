<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gross profit is SUM(retail_sale_items.profit_amount) — a snapshot taken
     * at sale time. A refund has to come off that, but scaling the per-line
     * rows down would destroy the record of what was originally sold.
     *
     * Instead the margin given back is accumulated here and subtracted when
     * profit is reported, so the line snapshots stay intact.
     *
     * Note this holds the refunded *profit*, not the refunded amount: handing
     * back 25,000 of revenue on a line that earned 5,000 costs 5,000 of profit,
     * because the goods come back into stock with their cost.
     */
    public function up(): void
    {
        Schema::table('retail_sales', function (Blueprint $table) {
            $table->decimal('refunded_profit', 14, 2)->default(0)->after('refunded_amount');
        });
    }

    public function down(): void
    {
        Schema::table('retail_sales', function (Blueprint $table) {
            $table->dropColumn('refunded_profit');
        });
    }
};
