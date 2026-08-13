<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_pesanan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->index();
            $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();
            $table->unsignedBigInteger('pesanan_id')->index();
            $table->foreign('pesanan_id')->references('id')->on('pesanan')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['payment_id', 'pesanan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_pesanan');
    }
};
