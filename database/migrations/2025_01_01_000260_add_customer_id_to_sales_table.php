<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // customer_name/customer_phone tetap dipertahankan sebagai snapshot teks
            // (walk-in tanpa dicatat sebagai pelanggan tetap masih boleh). customer_id
            // dipakai kalau transaksi terhubung ke pelanggan yang tercatat di tabel
            // customers (lihat CustomerService/SaleService).
            $table->foreignId('customer_id')->nullable()->after('egg_pickup_id')
                ->constrained('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
