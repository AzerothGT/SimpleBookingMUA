<?php

namespace App\Http\Resources;

use App\Models\Booking;
use App\Models\BookingService;
use App\Models\BookingStaffSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Booking */
class BookingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'user_id' => $this->user_id,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'client_address' => $this->client_address,
            'maps_url' => $this->maps_url,
            'maps_lat' => $this->maps_lat,
            'maps_lng' => $this->maps_lng,
            'client_requested_date' => $this->client_requested_date?->toDateString(),
            'client_requested_end_time' => $this->client_requested_end_time,
            'client_requested_ends_at' => $this->client_requested_ends_at,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'services' => $this->whenLoaded('bookingServices', function () {
                return $this->bookingServices->map(fn (BookingService $bs) => [
                    'id' => $bs->service_id,
                    'name' => $bs->service->name,
                    'price' => (float) $bs->service->price,
                    'qty' => $bs->qty,
                    'subtotal' => (float) $bs->service->price * $bs->qty,
                ]);
            }),
            'staff' => UserResource::make($this->whenLoaded('user')),
            'staff_schedules' => $this->whenLoaded('staffSchedules', fn () => $this->staffSchedules->map(fn (BookingStaffSchedule $schedule) => [
                'id' => $schedule->id,
                'user_id' => $schedule->user_id,
                'staff' => UserResource::make($schedule->user),
                'starts_at' => $schedule->starts_at,
                'ends_at' => $schedule->ends_at,
            ])),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            'activity_logs' => ActivityLogResource::collection($this->whenLoaded('activityLogs')),
        ];
    }
}
