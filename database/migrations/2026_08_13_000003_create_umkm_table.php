<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('umkm', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('nama_umkm', 150);
            $table->string('pemilik', 100);
            $table->string('alamat')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable();
            $table->string('status', 20)->default('aktif')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm');
    }
};
