<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('users')->restrictOnDelete();
            $table->string('destination'); // Tujuan (Misal: Proyek Pak Budi)
            $table->dateTime('departure_date'); // Waktu berangkat
            $table->dateTime('return_date')->nullable(); // Waktu kembali
            $table->enum('status', ['on_trip', 'completed'])->default('on_trip');
            
            // Keuangan & Komisi
            $table->decimal('revenue', 15, 2)->default(0); // Pendapatan dari perjalanan (jika armada komersial)
            $table->decimal('driver_commission', 15, 2)->default(0); // Komisi Driver
            $table->decimal('operational_cost', 15, 2)->default(0); // Biaya BBM, Tol, dll
            
            // Ceklis Kendaraan (Disimpan sebagai JSON agar ringkas)
            // Contoh format: {"ban": true, "dongkrak": true, "apar": false}
            $table->json('checklist')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_jobs');
    }
};