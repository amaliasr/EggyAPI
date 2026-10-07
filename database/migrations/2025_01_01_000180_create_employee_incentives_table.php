<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_incentives', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('incentive_program_id')->constrained('incentive_programs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_weight_kg', 10, 2)->default(0);
            $table->decimal('total_omzet', 14, 2)->default(0);
            $table->unsignedInteger('rank')->nullable();
            $table->decimal('calculated_amount', 14, 2)->default(0);
            $table->enum('status', ['estimasi', 'disetujui', 'dibayar'])->default('estimasi');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['incentive_program_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_incentives');
    }
};
