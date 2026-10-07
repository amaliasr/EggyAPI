<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incentive_tiers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('incentive_program_id')->constrained('incentive_programs')->cascadeOnDelete();
            $table->unsignedInteger('tier_order');
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->enum('rate_type', ['per_kg', 'nominal_tetap']);
            $table->decimal('rate_value', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incentive_tiers');
    }
};
