<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            
            // HOK (BR-04): min(durasi_jam / 10, 1)
            $table->decimal('hok', 5, 2)->default(0); 
            
            // Lembur (BR-05): (Gaji Harian / 10) * 2 * Jam Lembur
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('overtime_pay', 15, 2)->default(0);
            
            $table->timestamps();
            
            // Satu user hanya bisa punya 1 absensi per tanggal
            $table->unique(['user_id', 'date']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};