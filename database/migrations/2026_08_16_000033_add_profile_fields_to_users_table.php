<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto_profil')->nullable()->after('nama_lengkap');
            $table->text('alamat_utama')->nullable()->after('no_hp');
            $table->string('zona_pengiriman', 100)->nullable()->after('alamat_utama');
            $table->string('jenis_kelamin', 20)->nullable()->after('zona_pengiriman');
            $table->date('tanggal_lahir')->nullable()->after('jenis_kelamin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['foto_profil', 'alamat_utama', 'zona_pengiriman', 'jenis_kelamin', 'tanggal_lahir']);
        });
    }
};
