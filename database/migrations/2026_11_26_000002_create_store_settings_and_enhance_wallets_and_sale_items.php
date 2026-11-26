<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('Sentosa Bangunan');
            $table->text('store_address')->nullable();
            $table->string('store_phone', 50)->nullable();
            $table->string('bank_transfer_note', 255)->nullable();
            $table->boolean('show_customer_accounts')->default(true);
            $table->timestamps();
        });

        DB::table('store_settings')->insert([
            'store_name' => 'Sentosa Bangunan',
            'store_address' => 'Jl. Raya Km 6, Desa Amboyo Inti, Kecamatan Ngabang, Kabupaten Landak, Kalimantan Barat 79357',
            'store_phone' => null,
            'bank_transfer_note' => 'Mohon cantumkan kode pesanan pada berita transfer.',
            'show_customer_accounts' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('wallets', function (Blueprint $table) {
            $table->string('account_holder', 120)->nullable()->after('account_number');
            $table->boolean('show_on_catalog')->default(true)->after('is_active');
        });

        // Hapus angka rekening dummy bawaan awal agar tidak menyesatkan pembeli sebelum Owner mengisi rekening asli
        DB::table('wallets')
            ->where('account_number', '123-456-7890')
            ->update([
                'account_number' => null,
                'account_holder' => 'Sentosa Bangunan',
                'show_on_catalog' => false,
            ]);

        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('unit_label', 50)->nullable()->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('unit_label');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['account_holder', 'show_on_catalog']);
        });

        Schema::dropIfExists('store_settings');
    }
};
