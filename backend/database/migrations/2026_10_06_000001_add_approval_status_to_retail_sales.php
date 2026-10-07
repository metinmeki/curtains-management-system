<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A sale taken at the till sometimes can't be committed yet because the
     * supplier still has to confirm they can source the fabric. Orders already
     * carried that state; sales had nowhere to record it, so the till had to
     * either save a sale that might not happen or not record it at all.
     *
     * Deliberately separate from payment_status: a pending sale can still be
     * paid, partly paid or unpaid, and those two facts move independently.
     */
    public function up(): void
    {
        Schema::table('retail_sales', function (Blueprint $table) {
            $table->string('approval_status', 20)->default('accepted')->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('retail_sales', function (Blueprint $table) {
            $table->dropColumn('approval_status');
        });
    }
};
