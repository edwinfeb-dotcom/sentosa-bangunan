<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->integer('month'); // 1-12
            $table->integer('year');  // Contoh: 2026
            
            $table->decimal('basic_salary', 15, 2)->default(0); // Gaji Pokok
            $table->decimal('allowance', 15, 2)->default(0);    // Tunjangan
            $table->decimal('deduction', 15, 2)->default(0);    // Potongan (Kasbon/Utang)
            $table->decimal('net_salary', 15, 2)->default(0);   // Gaji Bersih
            
            $table->enum('status', ['draft', 'paid'])->default('draft');
            $table->timestamps();
            
            // Satu user hanya punya 1 slip gaji per bulan/tahun
            $table->unique(['user_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};