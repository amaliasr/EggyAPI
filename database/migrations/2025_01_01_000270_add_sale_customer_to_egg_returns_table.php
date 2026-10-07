<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah jalur retur BARU: pelanggan yang sudah beli (tercatat di sales)
 * lalu komplain/mengembalikan sebagian atau semua telurnya. Ini BERBEDA dari
 * jalur retur yang sudah ada (egg_pickup_id) — telur yang dibawa keliling tapi
 * tidak laku, dikembalikan ke gudang. Kedua jalur tetap berjalan berdampingan:
 * validasi "salah satu wajib diisi" (egg_pickup_id XOR sale_id) ada di
 * StoreEggReturnRequest, bukan di level database, supaya pesan errornya jelas.
 *
 * egg_category_id (kolom lama, tetap dipakai di kedua jalur) dipasangkan
 * dengan sale_id untuk menemukan sale_item terkait — desain ini mengikuti pola
 * yang sudah ada di EggPickupItem/SaleService (satu kategori telur muncul
 * maksimal sekali per transaksi), jadi tidak perlu kolom sale_item_id baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egg_returns', function (Blueprint $table) {
            $table->dropForeign(['egg_pickup_id']);
        });

        Schema::table('egg_returns', function (Blueprint $table) {
            $table->unsignedBigInteger('egg_pickup_id')->nullable()->change();
        });

        Schema::table('egg_returns', function (Blueprint $table) {
            $table->foreign('egg_pickup_id')->references('id')->on('egg_pickups')->restrictOnDelete();

            $table->foreignId('sale_id')->nullable()->after('egg_pickup_id')
                ->constrained('sales')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->after('sale_id')
                ->constrained('customers')->nullOnDelete();
            $table->decimal('refund_amount', 14, 2)->nullable()->after('customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('egg_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn('refund_amount');
        });

        Schema::table('egg_returns', function (Blueprint $table) {
            $table->dropForeign(['egg_pickup_id']);
        });

        Schema::table('egg_returns', function (Blueprint $table) {
            $table->unsignedBigInteger('egg_pickup_id')->nullable(false)->change();
        });

        Schema::table('egg_returns', function (Blueprint $table) {
            $table->foreign('egg_pickup_id')->references('id')->on('egg_pickups')->restrictOnDelete();
        });
    }
};
