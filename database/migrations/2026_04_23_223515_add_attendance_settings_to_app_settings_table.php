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
        Schema::table('app_settings', function (Blueprint $table) {
            $table->time('attendance_check_in_time')->default('08:00:00')->after('logo_path');
            $table->time('attendance_check_out_time')->default('17:00:00')->after('attendance_check_in_time');
            $table->unsignedInteger('attendance_tolerance_minutes')->default(10)->after('attendance_check_out_time');
            $table->time('attendance_absence_after_time')->default('09:00:00')->after('attendance_tolerance_minutes');
            $table->unsignedInteger('attendance_tardies_before_absence')->default(3)->after('attendance_absence_after_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_check_in_time',
                'attendance_check_out_time',
                'attendance_tolerance_minutes',
                'attendance_absence_after_time',
                'attendance_tardies_before_absence',
            ]);
        });
    }
};
