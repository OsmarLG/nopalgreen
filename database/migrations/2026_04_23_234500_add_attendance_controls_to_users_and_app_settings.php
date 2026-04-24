<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->date('attendance_starts_at')->nullable()->after('email_verified_at');
        });

        Schema::table('app_settings', function (Blueprint $table): void {
            $table->json('attendance_work_days')->nullable()->after('attendance_tardies_before_absence');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table): void {
            $table->dropColumn('attendance_work_days');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('attendance_starts_at');
        });
    }
};
