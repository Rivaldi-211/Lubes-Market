<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('reference_id', 64)->unique();
            $table->string('xendit_payment_request_id', 100)->nullable()->index();
            $table->string('xendit_payment_id', 100)->nullable()->index();
            $table->unsignedBigInteger('amount');
            $table->string('payment_method', 20)->default('QRIS');
            $table->string('status', 20)->default('CREATING')->index();
            $table->text('qr_string')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
