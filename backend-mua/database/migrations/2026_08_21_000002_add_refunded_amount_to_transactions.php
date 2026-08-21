<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            // Cumulative refunded amount, mirroring the Midtrans `refund_amount` field.
            $table->unsignedInteger('refunded_amount')->default(0)->after('gross_amount');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn('refunded_amount');
        });
    }
};
