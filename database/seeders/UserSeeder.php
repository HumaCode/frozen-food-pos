<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // =========================
        // SUPER ADMIN
        // =========================
        User::create([
            'name'              => 'Super Admin',
            'username'          => 'superadmin',
            'email'             => 'superadmin@example.com',
            'phone'             => '081234567890',
            'email_verified_at' => now(),
            'password'          => Hash::make('123'), // ganti di production
            'is_active'         => true,
            'remember_token'    => Str::random(10),
        ]);

        // =========================
        // USER BIASA (9 USER)
        // =========================
        for ($i = 1; $i <= 9; $i++) {
            User::create([
                'name'              => "User {$i}",
                'username'          => "user{$i}",
                'email'             => "user{$i}@example.com",
                'phone'             => '08' . rand(1111111111, 9999999999),
                'email_verified_at' => now(),
                'password'          => Hash::make('123'),
                'is_active'         => true,
                'remember_token'    => Str::random(10),
            ]);
        }
    }
}
