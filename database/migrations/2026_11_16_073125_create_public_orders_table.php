<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->string('customer_name');
            $table->string('phone');
            $table->enum('shipping_method', ['antar', 'ambil']);
            $table->text('address')->nullable(); // Wajib jika antar
            $table->enum('payment_method', ['cash', 'transfer']);
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['pending', 'paid', 'processed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_orders');
    }
};