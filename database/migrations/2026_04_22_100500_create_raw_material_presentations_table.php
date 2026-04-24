<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_presentations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('quantity', 12, 3);
            $table->foreignId('unit_id')->constrained('units')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('barcode')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_presentations');
    }
};
