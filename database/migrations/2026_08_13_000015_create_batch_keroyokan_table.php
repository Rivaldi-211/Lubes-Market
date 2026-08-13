<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('batch_keroyokan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pembeli_id');
            $table->foreign('pembeli_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('pembeli_id');
            $table->unsignedBigInteger('kelompok_keroyokan_id');
            $table->foreign('kelompok_keroyokan_id')->references('id')->on('kelompok_keroyokan')->restrictOnDelete();
            $table->index('kelompok_keroyokan_id');
            $table->unsignedInteger('target_jumlah');
            $table->decimal('total_harga', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_keroyokan');
    }
};
