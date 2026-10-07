<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('egg_category_id')->constrained('egg_categories')->restrictOnDelete();
            $table->foreignId('egg_price_id')->constrained('egg_prices')->restrictOnDelete();
            $table->decimal('price_per_kg', 12, 2);
            $table->unsignedInteger('quantity_pcs');
            $table->decimal('weight_kg', 10, 2);
            $table->unsignedInteger('crate_count')->default(0);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
