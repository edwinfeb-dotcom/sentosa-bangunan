<?php

namespace Database\Seeders;

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
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make('password'),
                'role' => $user['role'],
                'employee_discount' => $user['role'] === 'karyawan' ? 10.00 : 0,
                'employee_debt' => 0,
            ]);
        }
    }
}