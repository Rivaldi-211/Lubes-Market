<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pembeli_id');
            $table->foreign('pembeli_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('pembeli_id');
            $table->unsignedBigInteger('produk_id');
            $table->foreign('produk_id')->references('id')->on('produk')->restrictOnDelete();
            $table->index('produk_id');
            $table->unsignedInteger('jumlah')->default(1);
            $table->decimal('total_harga', 12, 2)->default(0);
            $table->string('metode_pembayaran', 20)->default('COD')->index();
            $table->string('bukti_pembayaran')->nullable();
            $table->string('alamat_pengiriman')->nullable();
            $table->string('no_hp_pembeli', 20)->nullable();
            $table->string('status', 20)->default('Menunggu')->index();
            $table->string('catatan')->nullable();
            $table->timestamp('tanggal_pesan')->useCurrent()->index();
            $table->timestamps();
            $table->index(['status', 'tanggal_pesan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
