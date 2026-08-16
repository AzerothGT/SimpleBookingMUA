<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('booking_code', 8)->nullable()->unique()->after('id');
        });

        foreach (Booking::query()->whereNull('booking_code')->cursor() as $booking) {
            do {
                $code = Str::upper(Str::random(8));
            } while (Booking::query()->where('booking_code', $code)->exists());

            $booking->forceFill(['booking_code' => $code])->saveQuietly();
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropUnique(['booking_code']);
            $table->dropColumn('booking_code');
        });
    }
};
