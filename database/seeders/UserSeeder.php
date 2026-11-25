<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Owner', 'email' => 'owner@sentosa.test', 'role' => 'owner'],
            ['name' => 'Admin Toko', 'email' => 'admin@sentosa.test', 'role' => 'admin'],
            ['name' => 'Kasir 1', 'email' => 'kasir@sentosa.test', 'role' => 'kasir'],
            ['name' => 'Gudang 1', 'email' => 'gudang@sentosa.test', 'role' => 'gudang'],
            ['name' => 'Driver 1', 'email' => 'driver@sentosa.test', 'role' => 'driver'],
            ['name' => 'Karyawan 1', 'email' => 'karyawan@sentosa.test', 'role' => 'karyawan'],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                    'employee_discount' => $user['role'] === 'karyawan' ? 10.00 : 0,
                    'employee_debt' => 0,
                ]
            );
        }

        $buyerCustomer = Customer::firstOrCreate(
            ['phone' => '081234567890'],
            [
                'name' => 'Budi Pembeli',
                'type' => 'reguler',
                'address' => 'Jl. Ahmad Yani No. 10, Pontianak',
                'points' => 25,
                'deposit_balance' => 0,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'pembeli@sentosa.test'],
            [
                'name' => 'Budi Pembeli',
                'password' => Hash::make('password'),
                'role' => 'pelanggan',
                'customer_id' => $buyerCustomer->id,
                'phone' => '081234567890',
                'address' => 'Jl. Ahmad Yani No. 10, Pontianak',
            ]
        );
    }
}