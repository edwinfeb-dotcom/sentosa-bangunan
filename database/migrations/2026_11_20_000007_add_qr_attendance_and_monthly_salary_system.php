<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('monthly_salary', 15, 2)->default(0)->after('daily_salary');
            $table->decimal('absence_deduction_per_day', 15, 2)->default(0)->after('monthly_salary');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpha'])->default('hadir')->after('user_id');
            $table->string('check_in_method', 30)->default('manual')->after('clock_out');
            $table->string('notes')->nullable()->after('overtime_pay');
        });

        Schema::create('attendance_qr_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('token', 120)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['date', 'is_active']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('monthly_salary_base', 15, 2)->default(0)->after('basic_salary');
            $table->unsignedInteger('working_days')->default(26)->after('monthly_salary_base');
            $table->unsignedInteger('present_days')->default(0)->after('working_days');
            $table->unsignedInteger('alpha_days')->default(0)->after('present_days');
            $table->decimal('alpha_deduction', 15, 2)->default(0)->after('alpha_days');
            $table->decimal('debt_deduction', 15, 2)->default(0)->after('alpha_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_salary_base',
                'working_days',
                'present_days',
                'alpha_days',
                'alpha_deduction',
                'debt_deduction',
            ]);
        });

        Schema::dropIfExists('attendance_qr_sessions');

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['status', 'check_in_method', 'notes']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['monthly_salary', 'absence_deduction_per_day']);
        });
    }
};
