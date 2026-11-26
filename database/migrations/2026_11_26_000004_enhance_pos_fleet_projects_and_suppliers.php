<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sales', 'manual_discount')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->decimal('manual_discount', 15, 2)->default(0)->after('discount_amount');
                $table->string('discount_note')->nullable()->after('manual_discount');
            });
        }

        if (!Schema::hasColumn('purchase_order_items', 'received_qty')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->integer('received_qty')->default(0)->after('qty');
            });
        }

        if (!Schema::hasColumn('vehicles', 'last_service_at')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->date('last_service_at')->nullable()->after('is_active');
                $table->date('next_service_at')->nullable()->after('last_service_at');
                $table->date('tax_due_at')->nullable()->after('next_service_at');
                $table->string('service_notes')->nullable()->after('tax_due_at');
            });
        }

        if (!Schema::hasColumn('projects', 'paid_amount')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('budget');
            });
        }

        if (!Schema::hasTable('project_payments')) {
            Schema::create('project_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
                $table->decimal('amount', 15, 2);
                $table->date('payment_date');
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_payments');

        if (Schema::hasColumn('projects', 'paid_amount')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('paid_amount');
            });
        }
        if (Schema::hasColumn('vehicles', 'last_service_at')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn(['last_service_at', 'next_service_at', 'tax_due_at', 'service_notes']);
            });
        }
        if (Schema::hasColumn('purchase_order_items', 'received_qty')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->dropColumn('received_qty');
            });
        }
        if (Schema::hasColumn('sales', 'manual_discount')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn(['manual_discount', 'discount_note']);
            });
        }
    }
};
