<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $productIds = DB::table('products')->pluck('id')->toArray();

        if (empty($productIds)) {
            $this->command->warn('Products table is empty. Run ProductSeeder first.');
            return;
        }

        /**
         * =========================
         * DISKON PER PRODUK (5)
         * =========================
         */
        for ($i = 1; $i <= 5; $i++) {
            $discountType = rand(0, 1) ? 'percentage' : 'nominal';

            DB::table('discounts')->insert([
                'name'           => "Diskon Produk {$i}",
                'type'           => 'product',
                'discount_type'  => $discountType,
                'value'          => $discountType === 'percentage'
                                    ? rand(5, 30) // %
                                    : rand(5_000, 30_000), // nominal
                'product_id'     => $productIds[array_rand($productIds)],
                'min_purchase'   => rand(20_000, 100_000),
                'is_active'      => true,
                'start_date'     => $now->copy()->subDays(rand(0, 3)),
                'end_date'       => $now->copy()->addDays(rand(7, 30)),
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        /**
         * =========================
         * DISKON TOTAL BELANJA (5)
         * =========================
         */
        for ($i = 1; $i <= 5; $i++) {
            $discountType = rand(0, 1) ? 'percentage' : 'nominal';

            DB::table('discounts')->insert([
                'name'           => "Diskon Total Belanja {$i}",
                'type'           => 'total',
                'discount_type'  => $discountType,
                'value'          => $discountType === 'percentage'
                                    ? rand(5, 20) // %
                                    : rand(10_000, 50_000), // nominal
                'product_id'     => null,
                'min_purchase'   => rand(100_000, 500_000),
                'is_active'      => true,
                'start_date'     => $now->copy()->subDays(rand(0, 5)),
                'end_date'       => $now->copy()->addDays(rand(14, 60)),
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }
}
