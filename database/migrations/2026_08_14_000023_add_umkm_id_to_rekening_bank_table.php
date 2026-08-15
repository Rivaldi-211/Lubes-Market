<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekening_bank', function (Blueprint $table) {
            $table->foreignId('umkm_id')->nullable()->after('id')->constrained('umkm')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('rekening_bank', function (Blueprint $table) {
            $table->dropForeign(['umkm_id']);
            $table->dropColumn('umkm_id');
        });
    }
};
