<?php

namespace Database\Seeders;

use App\Models\RetailClient;
use App\Models\RetailSale;
use App\Models\RetailSaleItem;
use App\Models\RetailExpense;
use App\Models\RetailPayment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RetailTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create test clients for stores
        $clients = [
            ['store_id' => 1, 'name' => 'أحمد محمد', 'phone' => '0501234567', 'notes' => 'عميل منتظم'],
            ['store_id' => 1, 'name' => 'فاطمة علي', 'phone' => '0502234567', 'notes' => 'عميل جديد'],
            ['store_id' => 2, 'name' => 'محمد سالم', 'phone' => '0503234567', 'notes' => ''],
        ];

        foreach ($clients as $client) {
            RetailClient::create($client);
        }

        // Create test sales
        $sales = [
            ['store_id' => 1, 'client_id' => 1, 'total_amount' => 5000, 'paid_amount' => 5000, 'remaining_amount' => 0, 'payment_status' => 'full', 'discount_amount' => 0, 'notes' => 'بيع ستائر قماشية'],
            ['store_id' => 1, 'client_id' => 2, 'total_amount' => 3000, 'paid_amount' => 0, 'remaining_amount' => 3000, 'payment_status' => 'debt', 'discount_amount' => 0, 'notes' => 'ديون'],
            ['store_id' => 1, 'client_id' => 1, 'total_amount' => 2500, 'paid_amount' => 1500, 'remaining_amount' => 1000, 'payment_status' => 'partial', 'discount_amount' => 100, 'notes' => 'دفع جزئي'],
            ['store_id' => 2, 'client_id' => 3, 'total_amount' => 4000, 'paid_amount' => 4000, 'remaining_amount' => 0, 'payment_status' => 'full', 'discount_amount' => 200, 'notes' => 'خصم خاص'],
        ];

        foreach ($sales as $sale) {
            $saleRecord = RetailSale::create($sale);

            // Add sale items
            RetailSaleItem::create([
                'sale_id' => $saleRecord->id,
                'material' => 'قماش قطن',
                'quantity' => 10,
                'unit_price' => $sale['total_amount'] / 10,
                'total_price' => $sale['total_amount']
            ]);
        }

        // Create test expenses
        $expenses = [
            ['store_id' => 1, 'category' => 'إيجار', 'amount' => 1000, 'date' => Carbon::today(), 'created_by' => 'admin'],
            ['store_id' => 1, 'category' => 'كهرباء', 'amount' => 500, 'date' => Carbon::today()->subDays(1), 'created_by' => 'admin'],
            ['store_id' => 1, 'category' => 'نقل', 'amount' => 300, 'date' => Carbon::today()->subDays(2), 'created_by' => 'admin'],
            ['store_id' => 2, 'category' => 'إيجار', 'amount' => 1200, 'date' => Carbon::today(), 'created_by' => 'admin'],
        ];

        foreach ($expenses as $expense) {
            RetailExpense::create($expense);
        }

        // Add test payments
        RetailPayment::create([
            'sale_id' => 3,
            'amount' => 1500,
            'notes' => 'دفعة أولى'
        ]);
    }
}
