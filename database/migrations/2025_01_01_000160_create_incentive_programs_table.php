<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incentive_programs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150);
            $table->enum('method', ['berat', 'persentase_omzet', 'peringkat', 'target']);
            $table->date('period_start');
            $table->date('period_end');
            $table->boolean('include_bentes')->default(false);
            $table->boolean('include_piutang')->default(false);
            $table->decimal('percentage_value', 5, 2)->nullable();
            $table->decimal('target_value_kg', 10, 2)->nullable();
            $table->decimal('flat_bonus_amount', 14, 2)->nullable();
            $table->enum('status', ['draft', 'aktif', 'ditutup'])->default('draft');
            $table->auditColumns();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incentive_programs');
    }
};
