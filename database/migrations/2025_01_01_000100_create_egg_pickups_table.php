<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egg_pickups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('pickup_code', 30)->unique();
            $table->dateTime('pickup_date');
            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->string('destination_area', 150)->nullable();
            $table->enum('status', ['dibawa', 'sebagian_terjual', 'terjual', 'dikembalikan'])->default('dibawa');
            $table->text('notes')->nullable();
            $table->enum('sync_status', ['synced', 'pending'])->default('synced');
            $table->auditColumns();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egg_pickups');
    }
};
