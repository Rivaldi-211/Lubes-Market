<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disbursements', function (Blueprint $table) {
            $table->unsignedBigInteger('rekening_bank_id')->nullable()->after('umkm_id');
            $table->foreign('rekening_bank_id')->references('id')->on('rekening_bank')->nullOnDelete();
            $table->json('rekening_bank_snapshot')->nullable()->after('rekening_bank_id');
            $table->unsignedBigInteger('requested_by')->nullable()->after('admin_id');
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('diajukan_at')->nullable()->after('catatan');
            $table->timestamp('ditolak_at')->nullable()->after('dibayar_at');
        });
    }

    public function down(): void
    {
        Schema::table('disbursements', function (Blueprint $table) {
            $table->dropForeign(['rekening_bank_id']);
            $table->dropForeign(['requested_by']);
            $table->dropColumn([
                'rekening_bank_id',
                'rekening_bank_snapshot',
                'requested_by',
                'diajukan_at',
                'ditolak_at',
            ]);
        });
    }
};
