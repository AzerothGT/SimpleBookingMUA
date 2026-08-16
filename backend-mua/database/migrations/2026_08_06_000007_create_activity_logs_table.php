<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('entity_type')->index(); // booking|transaction|service|user
            $table->uuid('entity_id')->nullable();
            $table->uuid('booking_id')->nullable()->index();
            $table->string('action');
            $table->text('detail')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');

            // Composite index untuk filter cepat
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
