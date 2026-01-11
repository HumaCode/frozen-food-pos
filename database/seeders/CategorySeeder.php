<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $categories = [
            'Frozen Chicken',
            'Frozen Beef',
            'Frozen Seafood',
            'Frozen Fish',
            'Frozen Shrimp',
            'Frozen Nugget',
            'Frozen Sausage',
            'Frozen Meatball',
            'Frozen Dumpling',
            'Frozen French Fries',
            'Frozen Tempura',
            'Frozen Tofu',
            'Frozen Vegetables',
            'Frozen Fruits',
            'Frozen Pizza',
            'Frozen Kebab',
            'Frozen Otak-Otak',
            'Frozen Siomay',
            'Frozen Cireng',
            'Frozen Dessert',
        ];

        foreach ($categories as $index => $name) {
            DB::table('categories')->insert([
                'name'       => $name,
                'image'      => null,
                'is_active'  => true,
                'sort_order' => $index + 1,
                'created_at'=> $now,
                'updated_at'=> $now,
            ]);
        }
    }
}
