<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['cash', 'bank', 'ewallet'])->default('cash');
            $table->string('account_number')->nullable();
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('cash_ledgers', function (Blueprint $table) {
            $table->foreignId('wallet_id')->nullable()->after('user_id')->constrained('wallets')->nullOnDelete();
            $table->string('category')->nullable()->after('type');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('wallet_id')->nullable()->after('sale_id')->constrained('wallets')->nullOnDelete();
        });

        // Default wallets so existing installations immediately work
        DB::table('wallets')->insert([
            [
                'name' => 'Kas Tunai (Laci)',
                'type' => 'cash',
                'account_number' => null,
                'initial_balance' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bank BCA Toko',
                'type' => 'bank',
                'account_number' => '123-456-7890',
                'initial_balance' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wallet_id');
        });

        Schema::table('cash_ledgers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wallet_id');
            $table->dropColumn('category');
        });

        Schema::dropIfExists('wallets');
    }
};
