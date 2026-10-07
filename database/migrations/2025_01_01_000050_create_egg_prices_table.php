<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egg_prices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('egg_category_id')->constrained('egg_categories')->restrictOnDelete();
            $table->decimal('price_per_kg', 12, 2);
            $table->date('effective_date');
            $table->enum('status', ['terjadwal', 'aktif', 'kedaluwarsa'])->default('terjadwal');
            $table->foreignId('set_by')->constrained('users')->restrictOnDelete();
            $table->string('notes', 255)->nullable();
            $table->auditColumns();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['egg_category_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egg_prices');
    }
};
