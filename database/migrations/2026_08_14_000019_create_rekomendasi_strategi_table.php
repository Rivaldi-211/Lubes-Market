<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekomendasi_strategi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('umkm_id');
            $table->foreign('umkm_id')->references('id')->on('umkm')->cascadeOnDelete();
            $table->string('judul', 200);
            $table->text('isi');
            $table->string('tipe', 50)->default('promosi'); // promosi | produk | harga | distribusi
            $table->string('periode', 7); // format YYYY-MM
            $table->boolean('dibaca')->default(false);
            $table->timestamps();

            $table->index('umkm_id');
            $table->index(['umkm_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_strategi');
    }
};
