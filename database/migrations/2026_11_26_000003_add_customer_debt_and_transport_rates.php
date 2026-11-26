<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'debt_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->decimal('debt_balance', 15, 2)->default(0)->after('deposit_balance');
                $table->decimal('total_spent_history', 15, 2)->default(0)->after('debt_balance');
            });
        }

        if (!Schema::hasTable('customer_debt_ledgers')) {
            Schema::create('customer_debt_ledgers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('type', ['in', 'out']); // in = bon/hutang baru, out = bayar/lunas
                $table->decimal('amount', 15, 2);
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transport_rates')) {
            Schema::create('transport_rates', function (Blueprint $table) {
                $table->id();
                $table->string('origin')->default('Toko Sentosa');
                $table->string('destination');
                $table->decimal('rate_4_m3', 15, 2)->default(0);
                $table->decimal('rate_6_m3', 15, 2)->default(0);
                $table->string('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_rates');
        Schema::dropIfExists('customer_debt_ledgers');

        if (Schema::hasColumn('customers', 'debt_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn(['debt_balance', 'total_spent_history']);
            });
        }
    }
};
