<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('origin')->nullable(); // Asal daerah
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->date('join_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->enum('employee_status', ['aktif', 'training', 'nonaktif'])->default('aktif');
            $table->enum('employee_category', ['karyawan tetap', 'kontrak', 'harian'])->default('karyawan tetap');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'photo', 'birth_date', 'birth_place', 'origin', 'address', 
                'phone', 'join_date', 'resign_date', 'employee_status', 'employee_category'
            ]);
        });
    }
};