<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_staff_schedules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('booking_id');
            $table->uuid('user_id');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['booking_id', 'user_id']);
            $table->index(['user_id', 'starts_at', 'ends_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE booking_staff_schedules ADD CONSTRAINT booking_staff_schedules_window_check CHECK (ends_at > starts_at)');
        }

        foreach (Booking::query()
            ->whereNotNull('user_id')
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->cursor() as $booking) {
            DB::table('booking_staff_schedules')->insert([
                'id' => (string) Str::uuid(),
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'starts_at' => $booking->starts_at,
                'ends_at' => $booking->ends_at,
                'created_at' => $booking->created_at,
                'updated_at' => $booking->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_staff_schedules');
    }
};
