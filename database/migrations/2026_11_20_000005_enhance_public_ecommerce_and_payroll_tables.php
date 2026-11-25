<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner','admin','kasir','gudang','driver','karyawan','pelanggan') NOT NULL DEFAULT 'karyawan'");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('role')->constrained('customers')->nullOnDelete();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('qty')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE public_orders MODIFY COLUMN status ENUM('pending','packed','shipped','completed','processed','cancelled') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('public_orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->decimal('shipping_cost', 15, 2)->default(0)->after('address');
            $table->text('notes')->nullable()->after('payment_method');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('bonus_notes')->nullable()->after('allowance');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('bonus_notes');
        });

        Schema::table('public_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['shipping_cost', 'notes']);
        });

        Schema::dropIfExists('cart_items');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
