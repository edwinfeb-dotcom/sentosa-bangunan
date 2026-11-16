<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'owner',
                'admin',
                'kasir',
                'gudang',
                'driver',
                'karyawan'
            ])->default('karyawan')->after('email');

            $table->decimal('employee_discount', 5, 2)->default(0)->after('role');
            $table->decimal('employee_debt', 15, 2)->default(0)->after('employee_discount');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'employee_discount', 'employee_debt']);
        });
    }
};