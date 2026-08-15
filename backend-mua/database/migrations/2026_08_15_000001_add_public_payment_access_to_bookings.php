<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('payment_access_token_hash', 255)->nullable()->unique();
            $table->timestamp('payment_access_token_expires_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropUnique(['payment_access_token_hash']);
            $table->dropColumn(['payment_access_token_hash', 'payment_access_token_expires_at']);
        });
    }
};
