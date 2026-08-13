# Booking Quantity per Service

**Date:** 2026-08-13
**Status:** Approved

## Executive Summary

Add quantity-per-service support to the booking flow. Each service in step 1 can be selected with a quantity (number of people). A pivot table `booking_service` replaces the single `service_id` column on `bookings`.

## Database Changes

### New table: `booking_service`

| Column | Type | Constraints |
|---|---|---|
| `id` | UUID | Primary Key |
| `booking_id` | UUID | FK → `bookings.id` ON DELETE CASCADE |
| `service_id` | UUID | FK → `services.id` ON DELETE RESTRICT |
| `qty` | integer | default 1, min 1 |
| `created_at` | timestamp | useCurrent |

**Unique constraint:** `(booking_id, service_id)` — prevents duplicate services per booking.

### Migration: Drop `service_id` from `bookings`

1. Drop FK `bookings_service_id_foreign`
2. Data migration: for each existing booking, insert into `booking_service` with qty=1
3. Drop column `service_id`

## Backend Changes

### New Model: `BookingService`

- `$fillable`: `booking_id`, `service_id`, `qty`
- `belongsTo(Booking)`, `belongsTo(Service)`

### Modified Model: `Booking`

- Remove `service(): BelongsTo(Service)`
- Add `bookingServices(): HasMany(BookingService)`
- Remove `service_id` from `$fillable`

### Modified: `StoreBookingRequest`

Validation rules:

| Field | Rule |
|---|---|
| `services` | `required`, `array`, `min:1` |
| `services.*.id` | `required`, `exists:services,id,is_active,true` |
| `services.*.qty` | `required`, `integer`, `min:1` |

Remove `service_id` from payload.

### Modified: `BookingController::store`

```php
$booking = Booking::create($request->validated());

foreach ($request->services as $service) {
    $booking->bookingServices()->create([
        'service_id' => $service['id'],
        'qty' => $service['qty'],
    ]);
}
```

### Modified: `BookingResource`

Add `services` array: `[{id, name, price, qty, subtotal}]`.

## Frontend Changes

### Form state

```js
// Before
serviceIds: []  // ['id1', 'id2']

// After
serviceItems: []  // [{ id: 'id1', qty: 2 }, { id: 'id2', qty: 1 }]
```

### Step 1 — Service cards

Each card:
- Checkbox (selected/unselected state)
- Name + description
- Price
- When selected: quantity controls `[−]` N `[+]`

**Interactions:**
- Checkbox toggle: add item with qty=1 / remove item
- `+` button: increment qty
- `−` button: decrement qty (min 1). If qty reaches 0, remove item.

### Cart summary (mobile only)

- `Service name × qty — subtotal`
- `Total: sum(price × qty)`

### Summary panel (desktop)

- Show `Service name × qty` per row with subtotal

### Step 4 review

- Nama, Telepon, Catatan (unchanged)
- Plus: services list with quantities

### API submit

```js
// Before (broken — form.serviceId is undefined)
createBooking({ service_id: form.serviceId })

// After
createBooking({
  services: form.serviceItems.map(item => ({
    id: item.id,
    qty: item.qty
  })),
  client_name: form.name,
  // ... rest unchanged
})
```

## Data Migration

For existing bookings:
```sql
INSERT INTO booking_service (id, booking_id, service_id, qty, created_at)
SELECT uuid(), id, service_id, 1, NOW() FROM bookings;
```

## Scope

- **Backend:** Migration, Model, Request, Controller, Resource
- **Frontend:** `BookingPage.jsx` (form state, step 1 cards, cart summary, submit), `App.css` (quantity control styles)
- **Not in scope:** Transaction calculation, reporting, admin panel changes, staff dashboard