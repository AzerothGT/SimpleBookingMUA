# Booking Quantity per Service Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow each service in a booking to have a quantity (number of people), replacing the single `service_id` with a pivot table `booking_service`.

**Architecture:** A `booking_service` pivot table stores `(booking_id, service_id, qty)`. The `service_id` column is dropped from `bookings`. Backend API accepts `services[{id, qty}]` instead of `service_id`. Frontend step 1 shows quantity controls per service card. Payment gross amount becomes `sum(qty × price)`.

**Tech Stack:** Laravel 11 + PHP 8.3, React 19 + Tailwind v4, MySQL, Pest tests

---

## File Structure

### Backend — Create
- `backend-mua/database/migrations/2026_08_13_000001_create_booking_service_table.php`
- `backend-mua/app/Models/BookingService.php`
- `backend-mua/database/factories/BookingServiceFactory.php`

### Backend — Modify
- `backend-mua/app/Models/Booking.php`
- `backend-mua/database/factories/BookingFactory.php`
- `backend-mua/app/Http/Requests/StoreBookingRequest.php`
- `backend-mua/app/Actions/Bookings/CreateBooking.php`
- `backend-mua/app/Actions/Transactions/CreateSnapTransaction.php`
- `backend-mua/app/Http/Resources/BookingResource.php`
- `backend-mua/app/Http/Controllers/BookingController.php`
- `backend-mua/database/seeders/TransactionSeeder.php`
- `backend-mua/tests/Pest.php`
- `backend-mua/tests/Feature/PublicBookingTest.php`
- `backend-mua/tests/Feature/ApiResponseConsistencyTest.php`
- `backend-mua/tests/Feature/FactoryInvariantsTest.php`
- `backend-mua/tests/Feature/SchemaComplianceTest.php`
- `backend-mua/tests/Feature/ActivityLoggingTest.php`
- `backend-mua/tests/Feature/BookingScheduleConcurrencyTest.php`
- `backend-mua/tests/Feature/ModelComplianceTest.php`

### Frontend — Modify
- `frontend-mua/src/pages/user/BookingPage.jsx`
- `frontend-mua/src/App.css`

---

### Task 1: Create migration for `booking_service` table

**Files:**
- Create: `backend-mua/database/migrations/2026_08_13_000001_create_booking_service_table.php`

- [ ] **Step 1: Create the migration file**

```php
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
```

- [ ] **Step 2: Run migration to verify it works**

```bash
cd backend-mua && php artisan migrate --path=database/migrations/2026_08_13_000001_create_booking_service_table.php
```
Expected: `Created table booking_service`

---

### Task 2: Create `BookingService` model + factory

**Files:**
- Create: `backend-mua/app/Models/BookingService.php`
- Create: `backend-mua/database/factories/BookingServiceFactory.php`

- [ ] **Step 1: Create model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingService extends Model
{
    use HasFactory;
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'booking_id',
        'service_id',
        'qty',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
```

- [ ] **Step 2: Create factory**

```php
<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingService;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingService>
 */
class BookingServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'service_id' => Service::factory(),
            'qty' => fake()->numberBetween(1, 5),
        ];
    }
}
```

- [ ] **Step 3: Add `BookingService` to the morph map in `AppServiceProvider` or wherever it's registered**

Check if there's a morph map registration. If not, skip this step.

---

### Task 3: Update `Booking` model

**Files:**
- Modify: `backend-mua/app/Models/Booking.php`

- [ ] **Step 1: Remove `service_id` from `$fillable` and `service()` relation, add `bookingServices()`**

```php
// BEFORE:
#[Fillable([
    'user_id',
    'service_id',
    ...
])]
class Booking extends Model
{
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
    ...
}

// AFTER:
#[Fillable([
    'user_id',
    // 'service_id' — REMOVED
    'client_name',
    ...
])]
class Booking extends Model
{
    public function bookingServices(): HasMany
    {
        return $this->hasMany(BookingService::class);
    }
    ...
}
```

- [ ] **Step 2: Add `use App\Models\BookingService;` import**

---

### Task 4: Update `BookingFactory`

**Files:**
- Modify: `backend-mua/database/factories/BookingFactory.php`

- [ ] **Step 1: Remove `service_id` from definition, add `afterCreating` to create pivot row**

```php
// BEFORE:
return [
    'service_id' => Service::factory(),
    ...
];

// AFTER:
return [
    // 'service_id' removed
    ...
];
```

- [ ] **Step 2: Add `afterCreating` callback**

```php
public function configure(): static
{
    return $this->afterCreating(function (Booking $booking) {
        $booking->bookingServices()->create([
            'service_id' => Service::factory(),
            'qty' => 1,
        ]);
    });
}
```

- [ ] **Step 3: Add `use App\Models\BookingService;` import if needed**

- [ ] **Step 4: Run factory invariants test to verify**

```bash
cd backend-mua && php artisan test --filter=FactoryInvariantsTest
```
Expected: Tests pass (the `$booking->service->is_active` assertion will fail — we'll fix that in a later task)

---

### Task 5: Update `StoreBookingRequest`

**Files:**
- Modify: `backend-mua/app/Http/Requests/StoreBookingRequest.php`

- [ ] **Step 1: Replace `service_id` validation with `services` array**

```php
// BEFORE:
return [
    'service_id' => [
        'required',
        Rule::exists('services', 'id')->where('is_active', true),
    ],
    ...
];

// AFTER:
return [
    'services' => ['required', 'array', 'min:1'],
    'services.*.id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
    'services.*.qty' => ['required', 'integer', 'min:1'],
    ...
];
```

---

### Task 6: Update `CreateBooking` action

**Files:**
- Modify: `backend-mua/app/Actions/Bookings/CreateBooking.php`

- [ ] **Step 1: Extract services from data, create pivot records**

```php
// BEFORE:
return DB::transaction(function () use ($data): Booking {
    $data['client_requested_ends_at'] = Carbon::createFromFormat(
        'Y-m-d H:i',
        $data['client_requested_date'].' '.$data['client_requested_end_time'],
    );
    $data['starts_at'] = null;
    $data['ends_at'] = $data['client_requested_ends_at'];
    $data['status'] = 'pending';

    $booking = Booking::create($data);
    ...
});

// AFTER:
return DB::transaction(function () use ($data): Booking {
    $services = $data['services'];
    unset($data['services']);

    $data['client_requested_ends_at'] = Carbon::createFromFormat(
        'Y-m-d H:i',
        $data['client_requested_date'].' '.$data['client_requested_end_time'],
    );
    $data['starts_at'] = null;
    $data['ends_at'] = $data['client_requested_ends_at'];
    $data['status'] = 'pending';

    $booking = Booking::create($data);

    foreach ($services as $service) {
        $booking->bookingServices()->create([
            'service_id' => $service['id'],
            'qty' => $service['qty'],
        ]);
    }

    $this->recordActivity->handle(
        null,
        $booking,
        'booking.created',
        booking: $booking,
    );

    return $booking;
});
```

---

### Task 7: Update `BookingResource`

**Files:**
- Modify: `backend-mua/app/Http/Resources/BookingResource.php`

- [ ] **Step 1: Replace `service_id` with `services` array**

```php
// BEFORE:
return [
    'id' => $this->id,
    'service_id' => $this->service_id,
    ...
    'service' => ServiceResource::make($this->whenLoaded('service')),
    ...
];

// Load in controller:
$booking->load('service')

// AFTER:
return [
    'id' => $this->id,
    // 'service_id' removed
    ...
    'services' => $this->whenLoaded('bookingServices', function () {
        return $this->bookingServices->map(fn (BookingService $bs) => [
            'id' => $bs->service_id,
            'name' => $bs->service->name,
            'price' => (float) $bs->service->price,
            'qty' => $bs->qty,
            'subtotal' => (float) $bs->service->price * $bs->qty,
        ]);
    }),
    ...
];

// Load in controller:
$booking->load('bookingServices.service')
```

- [ ] **Step 2: Add `use App\Models\BookingService;` import**

---

### Task 8: Update `BookingController` eager loads + OA docs

**Files:**
- Modify: `backend-mua/app/Http/Controllers/BookingController.php`

- [ ] **Step 1: Update `index()` — change `with(['user', 'service', ...])` to `with(['user', 'bookingServices.service', ...])`**

- [ ] **Step 2: Update `show()` — change `user', 'service', ...` to `user', 'bookingServices.service', ...`**

- [ ] **Step 3: Update OA `@OA\Post` annotation — replace `service_id` with `services` array**

```php
// OA properties:
// Replace:
new OA\Property(property: 'service_id', type: 'string', format: 'uuid'),
// With:
new OA\Property(
    property: 'services',
    type: 'array',
    items: new OA\Items(
        properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'qty', type: 'integer', minimum: 1),
        ],
    ),
),
```

- [ ] **Step 4: Update `required` OA field — replace `'service_id'` with `'services'`**

---

### Task 9: Update `CreateSnapTransaction` gross amount calculation

**Files:**
- Modify: `backend-mua/app/Actions/Transactions/CreateSnapTransaction.php`

- [ ] **Step 1: Change `$booking->service->price` to sum of `bookingServices`**

```php
// BEFORE:
$booking = Booking::query()->with('service')->lockForUpdate()->findOrFail($booking->id);
...
$grossAmount = (int) round((float) $booking->service->price);

// AFTER:
$booking = Booking::query()->with('bookingServices.service')->lockForUpdate()->findOrFail($booking->id);
...
$grossAmount = (int) round($booking->bookingServices->sum(fn ($bs) => (float) $bs->service->price * $bs->qty));
```

---

### Task 10: Create migration to drop `service_id` from `bookings`

**Files:**
- Create: `backend-mua/database/migrations/2026_08_13_000002_drop_service_id_from_bookings.php`

- [ ] **Step 1: Create the migration**

```php
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
        // Migrate existing data: create booking_service rows for existing bookings
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

        // Drop FK and column
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign('bookings_service_id_foreign');
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
```

- [ ] **Step 2: Run migration**

```bash
cd backend-mua && php artisan migrate
```

---

### Task 11: Update `TransactionSeeder`

**Files:**
- Modify: `backend-mua/database/seeders/TransactionSeeder.php`

- [ ] **Step 1: Change gross amount calculation**

```php
// BEFORE:
'gross_amount' => (int) $booking->service->price,

// AFTER:
'gross_amount' => (int) round($booking->bookingServices->sum(fn ($bs) => (float) $bs->service->price * $bs->qty)),
```

- [ ] **Step 2: Update eager load**

```php
// Add ->load('bookingServices.service') before accessing bookingServices
```

---

### Task 12: Update test helpers in `Pest.php`

**Files:**
- Modify: `backend-mua/tests/Pest.php`

- [ ] **Step 1: Update `validBooking()` helper**

```php
// BEFORE:
function validBooking(array $attributes = []): Booking
{
    return Booking::factory()
        ->for(Service::factory()->state(['is_active' => true]))
        ->create($attributes);
}

// AFTER:
function validBooking(array $attributes = []): Booking
{
    return Booking::factory()
        ->create($attributes);
}
```

Note: The `->for(Service::factory())` used the `service()` BelongsTo relation which no longer exists. The `afterCreating` callback in `BookingFactory` will create the pivot record instead.

---

### Task 13: Update `PublicBookingTest`

**Files:**
- Modify: `backend-mua/tests/Feature/PublicBookingTest.php`

- [ ] **Step 1: Update `publicBookingPayload` function**

```php
// BEFORE:
function publicBookingPayload(Service $service, array $overrides = []): array
{
    return array_merge([
        'service_id' => $service->id,
        ...
    ], $overrides);
}

// AFTER:
function publicBookingPayload(Service $service, array $overrides = []): array
{
    return array_merge([
        'services' => [['id' => $service->id, 'qty' => 1]],
        ...
    ], $overrides);
}
```

- [ ] **Step 2: Update test assertions that check `service_id` validation error**

The test `it('rejects inactive services and requested end times in the past')` validates `service_id`. Change to `services.0.id`.

```php
// BEFORE:
->assertJsonValidationErrors('service_id');

// AFTER:
->assertJsonValidationErrors('services.0.id');
```

- [ ] **Step 3: Update `it('sets safe defaults for public bookings')`**

The test checks `$booking->user_id->toBeNull()` etc. No change needed since `service_id` is no longer on the booking model.

---

### Task 14: Update `ApiResponseConsistencyTest`

**Files:**
- Modify: `backend-mua/tests/Feature/ApiResponseConsistencyTest.php`

- [ ] **Step 1: Update validation error key assertion**

```php
// BEFORE:
->and($response->json('errors.service_id'))->toBeArray();

// AFTER:
->and($response->json('errors.services'))->toBeArray();
```

- [ ] **Step 2: Update `it('hides internal relations from the public booking response')`**

Change payload to send `services` array and update expected keys.

```php
// BEFORE:
$payload = $this->postJson('/api/bookings', [
    'service_id' => $service->id,
    ...
])->assertCreated()->json();

expect($payload)->toHaveKeys(['id', 'status', 'service'])
    ->and($payload)->not->toHaveKeys(['staff', 'transactions', 'tasks', 'activity_logs']);

// AFTER:
$payload = $this->postJson('/api/bookings', [
    'services' => [['id' => $service->id, 'qty' => 1]],
    ...
])->assertCreated()->json();

expect($payload)->toHaveKeys(['id', 'status', 'services'])
    ->and($payload)->not->toHaveKeys(['staff', 'transactions', 'tasks', 'activity_logs', 'service']);
```

---

### Task 15: Update `FactoryInvariantsTest`

**Files:**
- Modify: `backend-mua/tests/Feature/FactoryInvariantsTest.php`

- [ ] **Step 1: Update `$booking->service->is_active` check**

```php
// BEFORE:
->and($booking->service->is_active)->toBeTrue()

// AFTER:
// Load the pivot relation and check the first service
$booking->load('bookingServices.service');
->and($booking->bookingServices->first()->service->is_active)->toBeTrue()
```

---

### Task 16: Update `SchemaComplianceTest`

**Files:**
- Modify: `backend-mua/tests/Feature/SchemaComplianceTest.php`

- [ ] **Step 1: Update FK check — remove `bookings.service_id`**

```php
// BEFORE:
->and($deleteRules)->toMatchArray([
    'service_images.service_id' => 'CASCADE',
    'bookings.user_id' => 'RESTRICT',
    'bookings.service_id' => 'RESTRICT',
    ...
]);

// AFTER:
->and($deleteRules)->toMatchArray([
    'service_images.service_id' => 'CASCADE',
    'bookings.user_id' => 'RESTRICT',
    'booking_service.booking_id' => 'CASCADE',
    'booking_service.service_id' => 'RESTRICT',
    ...
]);
```

- [ ] **Step 2: Update `it('defines ERD constraints and indexes')`**

The `bookings_schedule_check` constraint is on `bookings` table and is unaffected.

---

### Task 17: Update `ActivityLoggingTest`

**Files:**
- Modify: `backend-mua/tests/Feature/ActivityLoggingTest.php`

- [ ] **Step 1: Update payloads to use `services` array**

```php
// BEFORE:
$this->postJson('/api/bookings', [
    'service_id' => $service->id,
    ...
]);

// AFTER:
$this->postJson('/api/bookings', [
    'services' => [['id' => $service->id, 'qty' => 1]],
    ...
]);
```

- [ ] **Step 2: Update all occurrences in the file (there are 2: `it('records public booking creation...')` and `it('uses one consistent activity record...')`)**

---

### Task 18: Update `BookingScheduleConcurrencyTest`

**Files:**
- Modify: `backend-mua/tests/Feature/BookingScheduleConcurrencyTest.php`

- [ ] **Step 1: Remove `$bookings->pluck('service_id')` usage**

```php
// BEFORE:
$serviceIds = $bookings->pluck('service_id')->all();

// AFTER:
$serviceIds = DB::table('booking_service')->whereIn('booking_id', $bookingIds)->pluck('service_id')->all();
```

- [ ] **Step 2: The cleanup logic `Service::whereIn('id', $serviceIds)->delete()` still works — service IDs now come from the pivot table instead of the bookings column.**

---

### Task 19: Update `ModelComplianceTest`

**Files:**
- Modify: `backend-mua/tests/Feature/ModelComplianceTest.php`

- [ ] **Step 1: Update `Booking::factory()->for($service)->create()`**

The `for($service)` call uses the `service()` BelongsTo relation which no longer exists. Change to creating the pivot record manually.

```php
// BEFORE:
$booking = Booking::factory()->for($service)->create();

// AFTER:
$booking = Booking::factory()->create();
$booking->bookingServices()->create([
    'service_id' => $service->id,
    'qty' => 1,
]);
```

---

### Task 19b: Update `Service` model bookings relation + `UpdateService` action + `ServiceManagementTest`

**Files:**
- Modify: `backend-mua/app/Models/Service.php`
- Modify: `backend-mua/app/Actions/Services/UpdateService.php`
- Modify: `backend-mua/tests/Feature/ServiceManagementTest.php`

Tujuan: relasi `bookings()` di Service bergantung pada kolom `bookings.service_id` yang akan di-drop. Ganti menjadi relasi `belongsToMany` melalui pivot `booking_service`. Rule bisnis "service dengan booking aktif tidak bisa di-deactivate" dan test-nya harus tetap bekerja.

- [ ] **Step 1: Update `Service::bookings()` relation**

```php
// BEFORE:
use Illuminate\Database\Eloquent\Relations\HasMany;
...
public function bookings(): HasMany
{
    return $this->hasMany(Booking::class);
}

// AFTER:
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
...
public function bookings(): BelongsToMany
{
    return $this->belongsToMany(Booking::class, 'booking_service', 'service_id', 'booking_id')
        ->withPivot('qty');
}
```

Catatan: `UpdateService::handle()` tidak perlu diubah — `$service->bookings()->active()->exists()` tetap bekerja dengan relasi `belongsToMany` karena query `whereHas`-style tetap valid (relasi belongsToMany mendukung chaining scope `active()` pada model Booking).

- [ ] **Step 2: Update `ServiceManagementTest` — ganti `Booking::factory()->for($service)->create()`**

Ada 3 tempat: `it('rejects deactivation while active bookings exist')`, `it('treats zero as a deactivation request')`, dan `it('allows deactivation when only inactive bookings exist')`.

```php
// BEFORE:
Booking::factory()->for($service)->create(['status' => $status]);

// AFTER:
$booking = Booking::factory()->create(['status' => $status]);
$booking->bookingServices()->create([
    'service_id' => $service->id,
    'qty' => 1,
]);
```

- [ ] **Step 3: Run ServiceManagement test**

```bash
cd backend-mua && php artisan test --filter=ServiceManagementTest
```
Expected: Semua test pass.

---

### Task 20: Frontend — update form state + service cards

**Files:**
- Modify: `frontend-mua/src/pages/user/BookingPage.jsx`

- [ ] **Step 1: Change `emptyForm`**

```js
// BEFORE:
const emptyForm = {
  serviceIds: [],
  ...
}

// AFTER:
const emptyForm = {
  serviceItems: [],  // { id: string, qty: number }
  ...
}
```

- [ ] **Step 2: Update `selectedServices` memo**

```js
// BEFORE:
const selectedServices = useMemo(
  () => services.filter((s) => form.serviceIds.includes(String(s.id))),
  [services, form.serviceIds],
)

// AFTER:
const selectedServices = useMemo(
  () => services.filter((s) => form.serviceItems.some((item) => item.id === String(s.id))),
  [services, form.serviceItems],
)
```

- [ ] **Step 3: Update `validateStepOne`**

```js
// BEFORE:
if (!form.serviceIds.length) nextErrors.serviceIds = 'Pilih minimal satu layanan.'

// AFTER:
if (!form.serviceItems.length) nextErrors.serviceItems = 'Pilih minimal satu layanan.'
```

- [ ] **Step 4: Update service card rendering**

Replace the checkbox + label with a card that includes quantity controls when selected:

```jsx
{services.map((service) => {
  const item = form.serviceItems.find((i) => i.id === String(service.id))
  const selected = !!item
  return (
    <div className={`service-option ${selected ? 'selected' : ''}`} key={service.id}>
      <input type="checkbox" name="service" value={service.id} checked={selected}
        onChange={(e) => {
          if (e.target.checked) {
            setForm((prev) => ({
              ...prev,
              serviceItems: [...prev.serviceItems, { id: String(service.id), qty: 1 }],
            }))
          } else {
            setForm((prev) => ({
              ...prev,
              serviceItems: prev.serviceItems.filter((i) => i.id !== String(service.id)),
            }))
          }
        }}
      />
      <span>
        <strong>{service.name}</strong>
        {service.description ? <small>{service.description}</small> : null}
      </span>
      <b>{formatPrice(service.price)}</b>
      {selected && (
        <div className="qty-control">
          <button type="button" className="qty-btn" onClick={() => {
            setForm((prev) => {
              const updated = prev.serviceItems
                .map((i) => i.id === String(service.id) ? { ...i, qty: i.qty - 1 } : i)
                .filter((i) => i.qty > 0)
              return { ...prev, serviceItems: updated }
            })
          }}>−</button>
          <span className="qty-value">{item.qty}</span>
          <button type="button" className="qty-btn" onClick={() => {
            setForm((prev) => ({
              ...prev,
              serviceItems: prev.serviceItems.map((i) =>
                i.id === String(service.id) ? { ...i, qty: i.qty + 1 } : i
              ),
            }))
          }}>+</button>
        </div>
      )}
    </div>
  )
})}
```

- [ ] **Step 5: Update cart summary**

```jsx
// BEFORE:
{form.serviceIds.length > 0 && (
  <div className="cart-summary">
    ...
    <li key={s.id}>{s.name} — {formatPrice(s.price)}</li>
    ...
    <b>Total estimasi: {formatPrice(selectedServices.reduce((sum, s) => sum + s.price, 0))}</b>
  </div>
)}

// AFTER:
{form.serviceItems.length > 0 && (
  <div className="cart-summary">
    <strong>Layanan terpilih:</strong>
    <ul>
      {form.serviceItems.map((item) => {
        const service = services.find((s) => String(s.id) === item.id)
        if (!service) return null
        return (
          <li key={item.id}>
            {service.name} × {item.qty} — {formatPrice(service.price * item.qty)}
          </li>
        )
      })}
    </ul>
    <b>Total estimasi: {formatPrice(
      form.serviceItems.reduce((sum, item) => {
        const service = services.find((s) => String(s.id) === item.id)
        return sum + (service ? service.price * item.qty : 0)
      }, 0)
    )}</b>
  </div>
)}
```

- [ ] **Step 6: Update summary panel (line ~292)**

```jsx
// BEFORE:
<strong>{selectedServices.map(s => s.name).join(', ') || 'Belum memilih layanan'}</strong>
<small>{selectedServices.length ? `Total: ...` : 'Harga mulai'}</small>
...
<dd>{form.address || 'Belum diisi'}</dd>

// AFTER:
<strong>{form.serviceItems.map(item => {
  const s = services.find(sv => String(sv.id) === item.id)
  return s ? `${s.name} × ${item.qty}` : ''
}).join(', ') || 'Belum memilih layanan'}</strong>
<small>{form.serviceItems.length
  ? `Total: ${formatPrice(form.serviceItems.reduce((sum, item) => {
      const s = services.find(sv => String(sv.id) === item.id)
      return sum + (s ? s.price * item.qty : 0)
    }, 0))}`
  : 'Harga mulai'}</small>
```

- [ ] **Step 7: Update submit payload**

```js
// BEFORE:
await createBooking({
  service_id: form.serviceId,
  ...
})

// AFTER:
await createBooking({
  services: form.serviceItems.map((item) => ({
    id: item.id,
    qty: item.qty,
  })),
  ...
})
```

---

### Task 21: Frontend — CSS for quantity controls

**Files:**
- Modify: `frontend-mua/src/App.css`

- [ ] **Step 1: Add `.qty-control` and `.qty-btn` styles**

Add after the `.service-option b` rule (around line 85):

```css
  .qty-control { @apply flex items-center gap-1 self-center; }
  .qty-btn { @apply flex h-6 w-6 items-center justify-center border bg-[var(--paper)] text-xs font-bold leading-none transition; border-color: var(--line); color: var(--ink); }
  .qty-btn:hover { @apply border-[var(--green)] bg-[#f1f5e9]; }
  .qty-value { @apply w-5 text-center font-mono text-[.6875rem] font-medium; color: var(--ink); }
```

---

### Task 22: Run full test suite and fix

**Files:**
- All backend tests

- [ ] **Step 1: Run all backend tests**

```bash
cd backend-mua && php artisan test
```

- [ ] **Step 2: Fix any failing tests**

Address specific failures from the test output. Likely candidates:
- Tests that access `$booking->service` directly
- Tests that use `service_id` in payloads
- Tests that check `booking.service` response key
- Schema compliance test for FK checks

- [ ] **Step 3: Run again to confirm green**

```bash
cd backend-mua && php artisan test
```
Expected: All tests pass.

---

### Task 23: Frontend build check

**Files:**
- `frontend-mua/src/`

- [ ] **Step 1: Run lint check**

```bash
cd frontend-mua && npm run lint
```
Expected: No errors.

- [ ] **Step 2: Run build**

```bash
cd frontend-mua && npm run build
```
Expected: Build succeeds.