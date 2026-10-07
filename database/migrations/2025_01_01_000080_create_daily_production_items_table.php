<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_production_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('daily_production_id')->constrained('daily_productions')->cascadeOnDelete();
            $table->foreignId('egg_category_id')->constrained('egg_categories')->restrictOnDelete();
            $table->unsignedInteger('quantity_pcs');
            $table->decimal('weight_kg', 10, 2);
            $table->unsignedInteger('crate_count')->default(0);
            $table->decimal('estimated_weight_kg', 10, 2)->nullable();
            $table->boolean('weight_variance_flagged')->default(false);
            $table->string('notes', 255)->nullable();
            $table->auditColumns();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_production_items');
    }
};
