<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = DB::table('categories')->pluck('id')->toArray();

        if (empty($categoryIds)) {
            $this->command->warn('Categories table is empty. Run CategorySeeder first.');
            return;
        }

        $units = ['pcs', 'pack', 'kilogram', 'gram', 'box'];
        $now = Carbon::now();

        for ($i = 1; $i <= 50; $i++) {
            $buyPrice  = rand(10_000, 100_000);
            $sellPrice = $buyPrice + rand(5_000, 30_000);

            DB::table('products')->insert([
                'category_id'  => $categoryIds[array_rand($categoryIds)],
                'barcode'      => 'BR-' . strtoupper(Str::random(8)) . $i,
                'name'         => "Produk Frozen {$i}",
                'image'        => null,
                'buy_price'    => $buyPrice,
                'sell_price'   => $sellPrice,
                'stock'        => rand(0, 200),
                'unit'         => $units[array_rand($units)],
                'min_stock'    => rand(5, 20),
                'expired_date' => rand(0, 1)
                                    ? $now->copy()->addMonths(rand(1, 12))
                                    : null,
                'is_active'    => true,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }
}
