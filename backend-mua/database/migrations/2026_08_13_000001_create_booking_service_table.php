<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_service', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id');
            $table->uuid('service_id');
            $table->integer('qty')->default(1);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['booking_id', 'service_id']);
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_service');
    }
};