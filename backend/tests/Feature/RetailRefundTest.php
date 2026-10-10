<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Refunds move money, so the rules worth pinning are the ones whose breakage
 * is silent: the cap, the proportion across repeated refunds, and the fact
 * that refunding does not quietly clear what a client still owes.
 */
class RetailRefundTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        // 2026_06_30_000006_seed_default_stores already creates stores 1-4,
        // so this only fills the gap if that migration ever stops doing so.
        DB::table('stores')->updateOrInsert(
            ['id' => 1],
            ['name' => 'Store 1', 'created_at' => now(), 'updated_at' => now()]
        );

        $id = DB::table('users')->insertGetId([
            'name' => 'Admin', 'email' => 'admin@test.local',
            'password' => bcrypt('secret'), 'role' => 'admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return User::find($id);
    }

    /**
     * 4 metres at 25,000 = 100,000 revenue, 20,000 of margin, paid in full.
     */
    private function sale(int $total = 100000, int $paid = 100000): int
    {
        $saleId = DB::table('retail_sales')->insertGetId([
            'store_id'         => 1,
            'client_id'        => null,
            'total_amount'     => $total,
            'paid_amount'      => $paid,
            'remaining_amount' => max($total - $paid, 0),
            'payment_status'   => $total - $paid <= 0 ? 'full' : 'partial',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('retail_sale_items')->insert([
            'sale_id'       => $saleId,
            'material'      => 'قماش',
            'quantity'      => 4,
            'unit_price'    => 25000,
            'total_price'   => 100000,
            'profit_amount' => 20000,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return $saleId;
    }

    private function refund(int $saleId, float $amount)
    {
        return $this->postJson("/api/retail/sales/{$saleId}/refund", ['amount' => $amount]);
    }

    public function test_refund_reduces_total_and_paid_by_the_same_amount(): void
    {
        Sanctum::actingAs($this->admin());
        $id = $this->sale();

        $this->refund($id, 25000)->assertOk();

        $sale = DB::table('retail_sales')->find($id);
        $this->assertEquals(75000, $sale->total_amount);
        $this->assertEquals(75000, $sale->paid_amount);
        $this->assertEquals(25000, $sale->refunded_amount);
    }

    /**
     * The regression this suite exists for. Proportions were divided by the
     * sale's current total, which shrinks with each refund, while line profit
     * never does — so each later refund gave back more than its share. Two
     * refunds of 25,000 returned 25% then 33%: 11,667 instead of 10,000.
     */
    public function test_repeated_partial_refunds_stay_proportional(): void
    {
        Sanctum::actingAs($this->admin());
        $id = $this->sale();

        $this->refund($id, 25000)->assertOk();
        $this->refund($id, 25000)->assertOk();

        $sale = DB::table('retail_sales')->find($id);
        $this->assertEquals(50000, $sale->refunded_amount);
        $this->assertEquals(10000, $sale->refunded_profit, 'half the sale refunded must return half the margin');
    }

    public function test_fully_refunding_in_instalments_returns_the_whole_margin(): void
    {
        Sanctum::actingAs($this->admin());
        $id = $this->sale();

        foreach ([25000, 25000, 25000, 25000] as $amount) {
            $this->refund($id, $amount)->assertOk();
        }

        $sale = DB::table('retail_sales')->find($id);
        $this->assertEquals(0, $sale->total_amount);
        $this->assertEquals(100000, $sale->refunded_amount);
        $this->assertEquals(20000, $sale->refunded_profit);
    }

    public function test_refund_cannot_exceed_what_was_paid(): void
    {
        Sanctum::actingAs($this->admin());
        // Half paid: 40,000 of a 100,000 sale.
        $id = $this->sale(100000, 40000);

        $this->refund($id, 40001)->assertStatus(422);

        $sale = DB::table('retail_sales')->find($id);
        $this->assertEquals(100000, $sale->total_amount, 'a rejected refund must not change the sale');
        $this->assertEquals(0, $sale->refunded_amount);
    }

    /**
     * Returning a deposit must not also write off the balance the client still
     * owes for the goods they kept.
     */
    public function test_refund_leaves_outstanding_debt_untouched(): void
    {
        Sanctum::actingAs($this->admin());
        $id = $this->sale(120000, 50000); // owes 70,000

        $this->refund($id, 50000)->assertOk();

        $sale = DB::table('retail_sales')->find($id);
        $this->assertEquals(70000, $sale->remaining_amount);
        $this->assertEquals(70000, $sale->total_amount);
        $this->assertEquals(0, $sale->paid_amount);
    }

    /**
     * pay() does not cap a payment at the sale total, so a client can be
     * over-paid; refunding that must not leave a negative sale value.
     */
    public function test_total_never_goes_negative_on_an_overpaid_sale(): void
    {
        Sanctum::actingAs($this->admin());
        $id = $this->sale(100000, 150000);

        $this->refund($id, 150000)->assertOk();

        $sale = DB::table('retail_sales')->find($id);
        $this->assertEquals(0, $sale->total_amount);
        $this->assertGreaterThanOrEqual(0, $sale->remaining_amount);
    }

    public function test_refund_is_recorded_as_a_negative_ledger_row(): void
    {
        Sanctum::actingAs($this->admin());
        $id = $this->sale();

        $this->refund($id, 25000)->assertOk();

        $row = DB::table('retail_payments')->where('sale_id', $id)->first();
        $this->assertNotNull($row);
        $this->assertEquals(-25000, $row->amount);
        $this->assertTrue((bool) $row->is_refund);
    }

    public function test_zero_and_negative_amounts_are_rejected(): void
    {
        Sanctum::actingAs($this->admin());
        $id = $this->sale();

        $this->refund($id, 0)->assertStatus(422);
        $this->refund($id, -5000)->assertStatus(422);

        $this->assertEquals(0, DB::table('retail_sales')->find($id)->refunded_amount);
    }
}
