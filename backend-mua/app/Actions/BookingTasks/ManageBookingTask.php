<?php

namespace App\Actions\BookingTasks;

use App\Actions\ActivityLogs\RecordActivity;
use App\Models\Booking;
use App\Models\BookingTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ManageBookingTask
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function create(Booking $booking, User $actor, array $data): BookingTask
    {
        return DB::transaction(function () use ($booking, $actor, $data): BookingTask {
            $task = $booking->bookingTasks()->create($data);
            $this->log($task, $actor, 'task.created');

            return $task;
        });
    }

    public function update(BookingTask $task, User $actor, array $data): BookingTask
    {
        return DB::transaction(function () use ($task, $actor, $data): BookingTask {
            $wasDone = $task->is_done;
            $before = $task->only(array_keys($data));
            $task->update($data);

            if (array_key_exists('is_done', $data) && $wasDone !== $task->is_done) {
                $this->log($task, $actor, 'task.toggled', [
                    'before' => $before,
                    'after' => $task->only(array_keys($data)),
                ]);
            }

            return $task->refresh();
        });
    }

    public function delete(BookingTask $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor): void {
            $this->log($task, $actor, 'task.deleted');
            $task->delete();
        });
    }

    private function log(BookingTask $task, User $actor, string $action, ?array $meta = null): void
    {
        $this->recordActivity->handle(
            $actor,
            $task,
            $action,
            booking: $task->booking,
            meta: $meta,
        );
    }
}
