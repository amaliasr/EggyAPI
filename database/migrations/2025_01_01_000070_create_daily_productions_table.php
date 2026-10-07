<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_productions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->date('production_date');
            $table->foreignId('farm_source_id')->nullable()->constrained('farm_sources')->nullOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('total_qty_pcs')->default(0);
            $table->decimal('total_weight_kg', 10, 2)->default(0);
            $table->unsignedInteger('total_crates')->default(0);
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->enum('sync_status', ['synced', 'pending'])->default('synced');
            $table->auditColumns();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['production_date', 'recorded_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_productions');
    }
};
