<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egg_pickup_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('egg_pickup_id')->constrained('egg_pickups')->cascadeOnDelete();
            $table->foreignId('egg_category_id')->constrained('egg_categories')->restrictOnDelete();
            $table->unsignedInteger('quantity_pcs');
            $table->decimal('weight_kg', 10, 2);
            $table->unsignedInteger('crate_count')->default(0);
            $table->decimal('weight_sold_kg', 10, 2)->default(0);
            $table->decimal('weight_returned_kg', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egg_pickup_items');
    }
};
