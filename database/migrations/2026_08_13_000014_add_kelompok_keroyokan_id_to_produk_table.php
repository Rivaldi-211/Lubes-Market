<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->unsignedBigInteger('kelompok_keroyokan_id')->nullable()->after('kategori_id');
            $table->foreign('kelompok_keroyokan_id')->references('id')->on('kelompok_keroyokan')->nullOnDelete();
            $table->index('kelompok_keroyokan_id');
        });
    }

    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropForeign(['kelompok_keroyokan_id']);
            $table->dropColumn('kelompok_keroyokan_id');
        });
    }
};
