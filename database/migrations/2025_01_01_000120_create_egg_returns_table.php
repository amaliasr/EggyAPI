<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egg_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('egg_pickup_id')->constrained('egg_pickups')->restrictOnDelete();
            $table->foreignId('egg_category_id')->constrained('egg_categories')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('return_date');
            $table->unsignedInteger('quantity_pcs');
            $table->decimal('weight_kg', 10, 2);
            $table->unsignedInteger('crate_count')->default(0);
            $table->string('reason', 255);
            $table->auditColumns();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egg_returns');
    }
};
