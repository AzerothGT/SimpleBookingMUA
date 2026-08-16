<?php

namespace App\Actions\Bookings;

use App\Actions\ActivityLogs\RecordActivity;
use App\Models\Booking;
use App\Models\BookingStaffSchedule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignBookingSchedule
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Booking $booking, User $actor, array $data): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $data): Booking {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $existingSchedules = $booking->staffSchedules()->get()->keyBy('user_id');
            $endsAt = Carbon::parse($data['ends_at']);

            if ($existingSchedules->isNotEmpty() && ! $booking->ends_at->equalTo($endsAt)) {
                throw ValidationException::withMessages([
                    'ends_at' => 'All staff schedules must use the booking end time.',
                ]);
            }

            $before = $booking->only(['user_id', 'starts_at', 'ends_at']);
            $assignedSchedules = [];

            foreach ($data['staff'] as $staffData) {
                $staff = User::query()->lockForUpdate()->findOrFail($staffData['user_id']);
                $startsAt = Carbon::parse($staffData['starts_at']);

                if ($startsAt->greaterThanOrEqualTo($endsAt)) {
                    throw ValidationException::withMessages([
                        'staff.'.$staffData['user_id'].'.starts_at' => 'Each staff start time must be before the booking end time.',
                    ]);
                }

                $overlaps = BookingStaffSchedule::query()
                    ->where('user_id', $staff->id)
                    ->where('booking_id', '!=', $booking->id)
                    ->whereHas('booking', fn ($query) => $query->whereIn('status', ['pending', 'confirmed']))
                    ->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt)
                    ->lockForUpdate()
                    ->exists();

                if ($overlaps) {
                    throw ValidationException::withMessages([
                        'staff' => 'The staff member already has an overlapping booking.',
                    ]);
                }

                $schedule = $existingSchedules->get($staff->id);
                if ($schedule) {
                    $schedule->update([
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                    ]);
                } else {
                    $schedule = $booking->staffSchedules()->create([
                        'user_id' => $staff->id,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                    ]);
                }

                $assignedSchedules[] = [
                    'user_id' => $staff->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ];
            }

            $firstSchedule = $booking->staffSchedules()->orderBy('created_at')->first();
            if ($firstSchedule) {
                $booking->update([
                    'user_id' => $firstSchedule->user_id,
                    'starts_at' => $firstSchedule->starts_at,
                    'ends_at' => $firstSchedule->ends_at,
                ]);
            }

            $this->recordActivity->handle(
                $actor,
                $booking,
                'booking.schedule_adjusted',
                booking: $booking,
                meta: [
                    'before' => $before,
                    'after' => $booking->only(['user_id', 'starts_at', 'ends_at']),
                    'staff_schedules' => $assignedSchedules,
                ],
                detail: 'Booking schedule adjusted.',
            );

            return $booking->refresh();
        });
    }
}
