<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->uuid('service_id')->index();
            $table->string('client_name');
            $table->string('client_phone');
            $table->text('client_address');
            $table->string('maps_url')->nullable();
            $table->decimal('maps_lat', 10, 8)->nullable();
            $table->decimal('maps_lng', 11, 8)->nullable();
            $table->date('client_requested_date');
            $table->time('client_requested_end_time');
            $table->timestamp('client_requested_ends_at');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('pending'); // pending|confirmed|done|cancelled
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('restrict');

            // Index untuk cek ketersediaan per staff
            $table->index(['user_id', 'starts_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_schedule_check CHECK (starts_at IS NULL OR ends_at > starts_at)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
