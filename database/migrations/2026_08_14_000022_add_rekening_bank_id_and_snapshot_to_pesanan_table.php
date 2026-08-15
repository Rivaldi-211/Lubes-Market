<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->foreignId('rekening_bank_id')
                ->nullable()
                ->after('metode_pembayaran')
                ->constrained('rekening_bank')
                ->nullOnDelete();
            $table->string('rekening_bank_snapshot')->nullable()->after('rekening_bank_id');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropForeign(['rekening_bank_id']);
            $table->dropColumn(['rekening_bank_id', 'rekening_bank_snapshot']);
        });
    }
};
