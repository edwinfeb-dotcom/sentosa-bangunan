<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('type', ['in', 'out']); // in = Kas Masuk, out = Kas Keluar
            $table->decimal('amount', 15, 2);
            $table->string('description');
            // Referensi opsional (misal dari ID Penjualan atau ID Laporan)
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('date'); // Tanggal transaksi kas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_ledgers');
    }
};