<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->boolean('is_promo')->default(false)->index()->after('foto');
            $table->decimal('harga_promo', 12, 2)->nullable()->after('is_promo');
            $table->timestamp('promo_mulai')->nullable()->after('harga_promo');
            $table->timestamp('promo_selesai')->nullable()->after('promo_mulai');
            $table->string('label_promo', 100)->nullable()->after('promo_selesai');
        });
    }

    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropColumn(['is_promo', 'harga_promo', 'promo_mulai', 'promo_selesai', 'label_promo']);
        });
    }
};
