<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('cash','transfer','debt','employee_debt','deposit','customer_debt') NOT NULL");
            DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('draft','ordered','partial','received','cancelled') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('cash','transfer','debt','employee_debt','deposit') NOT NULL");
            DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('draft','ordered','received','cancelled') NOT NULL DEFAULT 'draft'");
        }
    }
};
