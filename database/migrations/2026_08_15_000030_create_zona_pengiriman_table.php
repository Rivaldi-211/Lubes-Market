<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zona_pengiriman', function (Blueprint $table) {
            $table->id();
            $table->string('nama_zona', 100);
            $table->text('keterangan')->nullable();
            $table->decimal('biaya', 10, 2)->default(0);
            $table->boolean('aktif')->default(true);
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zona_pengiriman');
    }
};
