<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('transaction_no', 30)->unique();
            $table->dateTime('sale_date');
            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('egg_pickup_id')->nullable()->constrained('egg_pickups')->nullOnDelete();
            $table->string('customer_name', 150)->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->enum('payment_method', ['tunai', 'transfer', 'qris', 'piutang']);
            $table->enum('payment_status', ['lunas', 'belum_lunas', 'sebagian'])->default('belum_lunas');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->string('proof_photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->enum('sync_status', ['synced', 'pending'])->default('synced');
            $table->auditColumns();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'sale_date']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
