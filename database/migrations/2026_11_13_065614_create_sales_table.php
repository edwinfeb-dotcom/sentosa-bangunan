<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
        $table->id();
        $table->string('invoice_number')->unique();
        $table->string('idempotency_key')->unique(); // Untuk BR-08
        $table->foreignId('cash_session_id')->constrained('cash_sessions')->restrictOnDelete();
        $table->foreignId('user_id')->constrained('users')->restrictOnDelete(); // Kasir
        $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
        $table->decimal('total_amount', 15, 2)->default(0);
        $table->decimal('discount_amount', 15, 2)->default(0);
        $table->enum('status', ['completed', 'cancelled'])->default('completed');
        $table->timestamps();
        $table->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
