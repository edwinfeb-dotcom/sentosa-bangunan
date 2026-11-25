<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['month', 'year']);
        });

        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->enum('section', ['piket', 'ceklis_pagi', 'ceklis_sore', 'cek_aset']);
            $table->string('title');
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Default checklist items for daily operations
        DB::table('checklist_templates')->insert([
            ['section' => 'piket', 'title' => 'Menyapu area toko & halaman depan', 'description' => 'Pastikan bebas debu semen dan sampah', 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'piket', 'title' => 'Merapikan rak display cat, keramik & perkakas', 'description' => 'Cek label harga dan posisi barang', 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'cek_aset', 'title' => 'Kondisi komputer kasir & printer thermal', 'description' => 'Pastikan kertas struk cukup dan koneksi lancar', 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'cek_aset', 'title' => 'Kondisi armada angkut & kendaraan operasional', 'description' => 'Cek BBM, ban, dan kebersihan bak', 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'ceklis_pagi', 'title' => 'Buka rolling door & nyalakan lampu toko', 'description' => 'Dilakukan sebelum jam 08.00', 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'ceklis_sore', 'title' => 'Hitung uang laci kasir & cocokkan dengan POS', 'description' => 'Catat selisih bila ada', 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'ceklis_sore', 'title' => 'Kunci pintu gudang, matikan AC/lampu & gembok gerbang', 'description' => 'Pastikan seluruh area aman sebelum pulang', 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_templates');
        Schema::dropIfExists('sales_targets');
    }
};
