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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->dateTime('expected_check_in_at');
            $table->dateTime('expected_check_out_at');
            $table->dateTime('absence_after_at');
            $table->unsignedInteger('tolerance_minutes')->default(10);
            $table->string('entry_code', 12);
            $table->string('exit_code', 12);
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->string('check_in_status', 20)->default('pending');
            $table->string('check_out_status', 20)->default('pending');
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedBigInteger('check_in_device_id')->nullable();
            $table->unsignedBigInteger('check_out_device_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'attendance_date']);
            $table->index(['attendance_date', 'check_in_status']);
            $table->index('check_in_device_id');
            $table->index('check_out_device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
