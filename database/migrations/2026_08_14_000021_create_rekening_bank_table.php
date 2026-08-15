<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekening_bank', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bank', 100);
            $table->string('nomor_rekening', 50);
            $table->string('atas_nama', 150);
            $table->boolean('aktif')->default(true);
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekening_bank');
    }
};
