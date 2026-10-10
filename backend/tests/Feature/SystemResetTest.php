<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The reset endpoint deletes trading history, so the tests that matter are the
 * ones proving it refuses when it should and stays inside its scope when it
 * does run. A reset that quietly takes the wrong store is worse than one that
 * fails loudly.
 */
class SystemResetTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'admin', ?int $storeId = null): User
    {
        foreach ([1, 2] as $id) {
            DB::table('stores')->updateOrInsert(
                ['id' => $id],
                ['name' => "Store {$id}", 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $id = DB::table('users')->insertGetId([
            'name' => ucfirst($role), 'email' => $role . '@test.local',
            'password' => bcrypt('secret'), 'role' => $role, 'store_id' => $storeId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return User::find($id);
    }

    /** One sale (with an item and a payment) plus one expense, per store. */
    private function seedTrading(int $storeId): int
    {
        $saleId = DB::table('retail_sales')->insertGetId([
            'store_id' => $storeId, 'client_id' => null,
            'total_amount' => 1000, 'paid_amount' => 1000, 'remaining_amount' => 0,
            'payment_status' => 'full', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('retail_sale_items')->insert([
            'sale_id' => $saleId, 'material' => 'x', 'quantity' => 1,
            'unit_price' => 1000, 'total_price' => 1000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('retail_payments')->insert([
            'sale_id' => $saleId, 'amount' => 1000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('retail_expenses')->insert([
            'store_id' => $storeId, 'category' => 'Rent', 'amount' => 50,
            'date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $saleId;
    }

    public function test_cashier_cannot_reset(): void
    {
        Sanctum::actingAs($this->user('cashier', 1));
        $this->seedTrading(1);

        $this->postJson('/api/settings/reset', ['confirm' => 'RESET'])->assertStatus(403);

        $this->assertEquals(1, DB::table('retail_sales')->count(), 'a refused reset must delete nothing');
    }

    public function test_reset_requires_the_typed_confirmation(): void
    {
        Sanctum::actingAs($this->user());
        $this->seedTrading(1);

        $this->postJson('/api/settings/reset', [])->assertStatus(422);
        $this->postJson('/api/settings/reset', ['confirm' => 'reset'])->assertStatus(422);
        $this->postJson('/api/settings/reset', ['confirm' => 'yes'])->assertStatus(422);

        $this->assertEquals(1, DB::table('retail_sales')->count());
    }

    /** The scoping guarantee: clearing one store must not touch another. */
    public function test_single_store_reset_leaves_other_stores_alone(): void
    {
        Sanctum::actingAs($this->user());
        $this->seedTrading(1);
        $this->seedTrading(2);

        $this->postJson('/api/settings/reset', ['store_id' => 1, 'confirm' => 'RESET'])->assertOk();

        $this->assertEquals(0, DB::table('retail_sales')->where('store_id', 1)->count());
        $this->assertEquals(1, DB::table('retail_sales')->where('store_id', 2)->count());
        $this->assertEquals(0, DB::table('retail_expenses')->where('store_id', 1)->count());
        $this->assertEquals(1, DB::table('retail_expenses')->where('store_id', 2)->count());
    }

    public function test_reset_cascades_to_sale_items_and_payments(): void
    {
        Sanctum::actingAs($this->user());
        $this->seedTrading(1);

        $this->postJson('/api/settings/reset', ['store_id' => 1, 'confirm' => 'RESET'])->assertOk();

        $this->assertEquals(0, DB::table('retail_sale_items')->count());
        $this->assertEquals(0, DB::table('retail_payments')->count());
    }

    /** Configuration must survive, or a reset becomes a rebuild. */
    public function test_reset_keeps_stores_users_and_clients(): void
    {
        Sanctum::actingAs($this->user());
        DB::table('retail_clients')->insert([
            'store_id' => 1, 'name' => 'Ahmed', 'phone' => '0770',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->seedTrading(1);

        // Counted rather than hardcoded: a seed migration already creates
        // stores, so the invariant is that a reset changes none of these.
        $stores  = DB::table('stores')->count();
        $users   = DB::table('users')->count();
        $clients = DB::table('retail_clients')->count();

        $this->postJson('/api/settings/reset', ['confirm' => 'RESET'])->assertOk();

        $this->assertEquals($stores, DB::table('stores')->count());
        $this->assertEquals($users, DB::table('users')->count());
        $this->assertEquals($clients, DB::table('retail_clients')->count());
        $this->assertGreaterThan(0, $clients, 'the fixture must actually have created a client');
    }

    public function test_all_stores_reset_clears_everything(): void
    {
        Sanctum::actingAs($this->user());
        $this->seedTrading(1);
        $this->seedTrading(2);

        $res = $this->postJson('/api/settings/reset', ['confirm' => 'RESET'])->assertOk();

        $this->assertEquals(0, DB::table('retail_sales')->count());
        $this->assertEquals(2, $res->json('cleared.sales'), 'the response reports what it removed');
    }

    public function test_unknown_store_is_rejected(): void
    {
        Sanctum::actingAs($this->user());
        $this->seedTrading(1);

        $this->postJson('/api/settings/reset', ['store_id' => 999, 'confirm' => 'RESET'])->assertStatus(404);

        $this->assertEquals(1, DB::table('retail_sales')->count());
    }

    public function test_preview_counts_without_deleting(): void
    {
        Sanctum::actingAs($this->user());
        $this->seedTrading(1);

        $res = $this->getJson('/api/settings/reset/preview?store_id=1')->assertOk();

        $this->assertEquals(1, $res->json('data.sales'));
        $this->assertEquals(1, $res->json('data.expenses'));
        $this->assertEquals(1, DB::table('retail_sales')->count(), 'preview must not delete');
    }

    public function test_cashier_cannot_preview(): void
    {
        Sanctum::actingAs($this->user('cashier', 1));
        $this->getJson('/api/settings/reset/preview')->assertStatus(403);
    }

    public function test_cashier_cannot_change_settings(): void
    {
        Sanctum::actingAs($this->user('cashier', 1));
        $this->putJson('/api/settings/app', ['default_currency' => 'USD'])->assertStatus(403);
    }

    public function test_admin_can_save_the_new_defaults(): void
    {
        Sanctum::actingAs($this->user());

        $this->putJson('/api/settings/app', [
            'default_language' => 'ar',
            'default_currency' => 'USD',
        ])->assertOk();

        $settings = DB::table('app_settings')->pluck('value', 'key');
        $this->assertEquals('ar', $settings['default_language']);
        $this->assertEquals('USD', $settings['default_currency']);
    }

    public function test_invalid_default_values_are_rejected(): void
    {
        Sanctum::actingAs($this->user());

        $this->putJson('/api/settings/app', ['default_language' => 'fr'])->assertStatus(422);
        $this->putJson('/api/settings/app', ['default_currency' => 'EUR'])->assertStatus(422);
    }

    /** Saving one section must not blank the others. */
    public function test_saving_defaults_leaves_the_exchange_rate_intact(): void
    {
        Sanctum::actingAs($this->user());
        $before = DB::table('app_settings')->where('key', 'usd_to_iqd_rate')->value('value');

        $this->putJson('/api/settings/app', ['default_currency' => 'USD'])->assertOk();

        $this->assertEquals($before, DB::table('app_settings')->where('key', 'usd_to_iqd_rate')->value('value'));
    }
}
