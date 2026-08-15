<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->decimal('ongkos_kirim', 10, 2)->default(0)->after('total_harga');
            $table->decimal('biaya_packing', 10, 2)->default(0)->after('ongkos_kirim');
            $table->decimal('komisi_admin', 10, 2)->default(0)->after('biaya_packing');
            $table->decimal('pendapatan_penjual', 10, 2)->default(0)->after('komisi_admin');
            $table->string('opsi_packing', 50)->nullable()->after('catatan');
            $table->string('zona_pengiriman', 100)->nullable()->after('alamat_pengiriman');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropColumn(['ongkos_kirim', 'biaya_packing', 'komisi_admin', 'pendapatan_penjual', 'opsi_packing', 'zona_pengiriman']);
        });
    }
};
