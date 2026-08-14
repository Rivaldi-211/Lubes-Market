<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('umkm', function (Blueprint $table) {
            $table->string('kategori_usaha', 100)->nullable()->after('deskripsi');
            $table->year('tahun_berdiri')->nullable()->after('kategori_usaha');
            $table->unsignedSmallInteger('jumlah_karyawan')->default(1)->after('tahun_berdiri');
            $table->string('instagram')->nullable()->after('jumlah_karyawan');
        });
    }

    public function down(): void
    {
        Schema::table('umkm', function (Blueprint $table) {
            $table->dropColumn(['kategori_usaha', 'tahun_berdiri', 'jumlah_karyawan', 'instagram']);
        });
    }
};
