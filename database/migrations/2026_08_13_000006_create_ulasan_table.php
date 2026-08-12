<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ulasan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pesanan_id');
            $table->unique('pesanan_id');
            $table->foreign('pesanan_id')->references('id')->on('pesanan')->cascadeOnDelete();
            $table->unsignedBigInteger('produk_id');
            $table->foreign('produk_id')->references('id')->on('produk')->cascadeOnDelete();
            $table->index('produk_id');
            $table->unsignedBigInteger('pembeli_id');
            $table->foreign('pembeli_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('pembeli_id');
            $table->unsignedTinyInteger('rating')->default(5)->index();
            $table->text('komentar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ulasan');
    }
};
