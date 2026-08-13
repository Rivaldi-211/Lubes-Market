<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unique('xendit_payment_request_id', 'payments_xendit_payment_request_id_unique');
            $table->unique('xendit_payment_id', 'payments_xendit_payment_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_xendit_payment_request_id_unique');
            $table->dropUnique('payments_xendit_payment_id_unique');
        });
    }
};
