<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('umkm_id');
            $table->foreign('umkm_id')->references('id')->on('umkm')->cascadeOnDelete();
            $table->decimal('jumlah', 12, 2);
            $table->string('status', 20)->default('dibayar');
            $table->text('catatan')->nullable();
            $table->timestamp('dibayar_at')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('disbursement_pesanan', function (Blueprint $table) {
            $table->unsignedBigInteger('disbursement_id');
            $table->unsignedBigInteger('pesanan_id');
            $table->primary(['disbursement_id', 'pesanan_id']);
            $table->foreign('disbursement_id')->references('id')->on('disbursements')->cascadeOnDelete();
            $table->foreign('pesanan_id')->references('id')->on('pesanan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disbursement_pesanan');
        Schema::dropIfExists('disbursements');
    }
};
