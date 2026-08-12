<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('umkm_id');
            $table->foreign('umkm_id')->references('id')->on('umkm')->cascadeOnDelete();
            $table->index('umkm_id');
            $table->unsignedBigInteger('kategori_id');
            $table->foreign('kategori_id')->references('id')->on('kategori')->restrictOnDelete();
            $table->index('kategori_id');
            $table->string('nama_produk', 150);
            $table->decimal('harga', 12, 2)->default(0);
            $table->string('stok_status', 20)->default('Ready')->index();
            $table->unsignedInteger('stok_jumlah')->default(10);
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();
            $table->index(['umkm_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
