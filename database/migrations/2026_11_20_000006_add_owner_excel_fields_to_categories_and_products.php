<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->after('id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('category_id')->constrained('suppliers')->nullOnDelete();
            $table->string('warehouse_name', 100)->nullable()->after('unit');
            $table->string('rack_location', 100)->nullable()->after('warehouse_name');
            $table->integer('stock_maximum')->default(0)->after('stock_minimum');
            $table->string('margin_code', 20)->nullable()->after('selling_price');
            $table->string('secondary_unit', 50)->nullable()->after('margin_code');
            $table->decimal('secondary_ratio', 10, 2)->nullable()->after('secondary_unit');
            $table->string('secondary_margin_code', 20)->nullable()->after('secondary_ratio');
            $table->decimal('secondary_price', 15, 2)->nullable()->after('secondary_margin_code');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn([
                'warehouse_name',
                'rack_location',
                'stock_maximum',
                'margin_code',
                'secondary_unit',
                'secondary_ratio',
                'secondary_margin_code',
                'secondary_price',
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
