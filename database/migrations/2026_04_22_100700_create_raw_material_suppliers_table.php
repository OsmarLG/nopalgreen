<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_suppliers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('supplier_sku')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['raw_material_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_suppliers');
    }
};
