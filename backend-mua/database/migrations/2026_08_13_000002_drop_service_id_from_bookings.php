<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-only: migrate existing data to pivot table
        if (DB::getDriverName() !== 'sqlite') {
            DB::table('bookings')
                ->whereNotNull('service_id')
                ->orderBy('id')
                ->each(function ($booking) {
                    DB::table('booking_service')->insert([
                        'id' => Str::uuid()->toString(),
                        'booking_id' => $booking->id,
                        'service_id' => $booking->service_id,
                        'qty' => 1,
                        'created_at' => now(),
                    ]);
                });
        }

        // SQLite: drop index before column (SQLite can't drop indexed columns)
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS "bookings_service_id_index"');
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->uuid('service_id')->nullable()->after('user_id');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('restrict');
        });
    }
};