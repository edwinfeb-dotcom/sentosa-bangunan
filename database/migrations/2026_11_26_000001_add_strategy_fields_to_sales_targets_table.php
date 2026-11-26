<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_targets', function (Blueprint $table) {
            $table->decimal('target_profit', 15, 2)->default(0)->after('target_amount');
            $table->decimal('max_expense', 15, 2)->default(0)->after('target_profit');
            $table->unsignedTinyInteger('working_days')->default(26)->after('max_expense');
            $table->foreignId('focus_category_id')->nullable()->after('working_days')->constrained('categories')->nullOnDelete();
            $table->text('strategy_plan')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('sales_targets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('focus_category_id');
            $table->dropColumn(['target_profit', 'max_expense', 'working_days', 'strategy_plan']);
        });
    }
};
