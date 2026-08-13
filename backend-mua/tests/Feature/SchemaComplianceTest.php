<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\ServiceImage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('uses the ERD table and timestamp definitions', function () {
    expect(Schema::hasTable('personal_access_tokens'))->toBeTrue()
        ->and(Schema::hasTable('sessions'))->toBeFalse()
        ->and(Schema::hasTable('auth_sessions'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'updated_at'))->toBeFalse()
        ->and(Schema::hasColumn('services', 'updated_at'))->toBeFalse()
        ->and(Schema::hasColumn('service_images', 'updated_at'))->toBeFalse()
        ->and(Schema::hasColumn('booking_tasks', 'updated_at'))->toBeFalse()
        ->and(Schema::hasColumn('activity_logs', 'updated_at'))->toBeFalse();
});

it('does not store session payloads in the database', function () {
    expect(config('session.driver'))->not->toBe('database')
        ->and(Schema::hasTable('sessions'))->toBeFalse();
});

it('defines ERD constraints and indexes', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('MySQL schema introspection requires the MySQL test connection.');
    }

    $users = DB::select('SHOW CREATE TABLE users')[0]->{'Create Table'};
    $bookings = DB::select('SHOW CREATE TABLE bookings')[0]->{'Create Table'};
    $transactions = DB::select('SHOW CREATE TABLE transactions')[0]->{'Create Table'};
    $triggers = collect(DB::select("SHOW TRIGGERS LIKE 'service_images'"))->pluck('Trigger');

    expect($users)->toContain('users_role_check')
        ->and($bookings)->toContain('bookings_schedule_check')
        ->and($transactions)->toContain('transactions_gross_amount_check')
        ->and($transactions)->toContain('varchar(50)')
        ->and($triggers)->toContain('service_images_one_cover_insert')
        ->and($triggers)->toContain('service_images_one_cover_update');
});

it('defines required foreign keys and indexes', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('MySQL schema introspection requires the MySQL test connection.');
    }

    $indexes = collect(DB::select('SHOW INDEX FROM bookings'))->pluck('Key_name');
    $logIndexes = collect(DB::select('SHOW INDEX FROM activity_logs'))->pluck('Key_name');
    $deleteRules = collect(DB::select(<<<'SQL'
        SELECT kcu.TABLE_NAME, kcu.COLUMN_NAME, rc.DELETE_RULE
        FROM information_schema.REFERENTIAL_CONSTRAINTS rc
        JOIN information_schema.KEY_COLUMN_USAGE kcu
          ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
         AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
        WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
        SQL))->mapWithKeys(fn ($foreignKey) => [
        $foreignKey->TABLE_NAME.'.'.$foreignKey->COLUMN_NAME => $foreignKey->DELETE_RULE,
    ]);

    expect($indexes)->toContain('bookings_user_id_starts_at_index')
        ->and($logIndexes)->toContain('activity_logs_entity_type_entity_id_index')
        ->and($deleteRules)->toMatchArray([
            'service_images.service_id' => 'CASCADE',
            'bookings.user_id' => 'RESTRICT',
            'booking_service.booking_id' => 'CASCADE',
            'booking_service.service_id' => 'RESTRICT',
            'booking_tasks.booking_id' => 'CASCADE',
            'transactions.booking_id' => 'RESTRICT',
            'transactions.user_id' => 'RESTRICT',
            'activity_logs.user_id' => 'RESTRICT',
            'activity_logs.booking_id' => 'SET NULL',
        ]);
});

it('rejects values blocked by MySQL check constraints', function (string $case) {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('MySQL check constraints require the MySQL test connection.');
    }

    $write = match ($case) {
        'role' => fn () => User::factory()->create(['role' => 'client']),
        'schedule' => fn () => Booking::factory()->create([
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->subMinute(),
        ]),
        'amount' => fn () => Transaction::factory()->create(['gross_amount' => 0]),
    };

    expect($write)->toThrow(QueryException::class);
})->with([
    'invalid user role' => 'role',
    'invalid booking window' => 'schedule',
    'zero gross amount' => 'amount',
]);

it('enforces unique transaction order IDs', function () {
    $orderId = 'ORDER-UNIQUE';
    Transaction::factory()->create(['order_id' => $orderId]);

    expect(fn () => Transaction::factory()->create(['order_id' => $orderId]))
        ->toThrow(QueryException::class);
});

it('allows only one cover image per service', function () {
    $service = Service::factory()->create();
    ServiceImage::factory()->for($service)->cover()->create();

    expect(fn () => ServiceImage::factory()->for($service)->cover()->create())
        ->toThrow(QueryException::class);
});
