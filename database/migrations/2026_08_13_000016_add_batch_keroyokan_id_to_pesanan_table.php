<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_keroyokan_id')->nullable()->after('pembeli_id');
            $table->foreign('batch_keroyokan_id')->references('id')->on('batch_keroyokan')->nullOnDelete();
            $table->index('batch_keroyokan_id');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropForeign(['batch_keroyokan_id']);
            $table->dropColumn('batch_keroyokan_id');
        });
    }
};
