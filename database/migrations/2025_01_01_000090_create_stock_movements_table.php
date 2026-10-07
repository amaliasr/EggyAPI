<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('egg_category_id')->constrained('egg_categories')->restrictOnDelete();
            $table->enum('movement_type', ['produksi_masuk', 'pengambilan_keluar', 'pengembalian_masuk', 'penyesuaian']);
            $table->string('reference_type', 50);
            $table->unsignedBigInteger('reference_id');
            $table->integer('quantity_pcs');
            $table->decimal('weight_kg', 10, 2);
            $table->integer('balance_after_qty');
            $table->decimal('balance_after_weight_kg', 10, 2);
            $table->string('notes', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['egg_category_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
