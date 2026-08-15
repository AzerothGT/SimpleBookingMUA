# Public Booking Payment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let unauthenticated public clients track a booking and start Midtrans Snap payment after staff assigns a schedule, without exposing authenticated booking data.

**Architecture:** Add a hashed, expiring bearer credential to each public booking. A dedicated public controller validates that credential and returns a narrow public status resource or creates an idempotent Snap transaction through the existing action, using a system user as the transaction actor. The React booking success screen stores the credential, polls status, and enables payment only when the backend reports a schedule.

**Tech Stack:** Laravel 13, Eloquent, Pest 5, React 19, Vite, browser Fetch API, Midtrans Snap.js.

---

## File Map

- Create `backend-mua/database/migrations/2026_08_15_000001_add_public_payment_access_to_bookings.php` for token hash and expiry columns/index.
- Modify `backend-mua/app/Models/Booking.php` to fill/cast token fields and expose token verification helpers.
- Modify `backend-mua/app/Actions/Bookings/CreateBooking.php` to generate the raw token, persist only its hash, and expose it to the creation response without persisting the raw value.
- Create `backend-mua/app/Http/Resources/PublicBookingResource.php` for the minimal public status/payment payload.
- Create `backend-mua/app/Http/Controllers/PublicBookingController.php` for token-authenticated status and Snap endpoints.
- Create `backend-mua/app/Http/Requests/PublicBookingRequest.php` for token validation and rate-limit-friendly request input.
- Modify `backend-mua/routes/api.php` to register public status and Snap routes with throttling.
- Modify `backend-mua/app/Actions/Transactions/CreateSnapTransaction.php` to accept a nullable/system actor or an explicit actor abstraction while preserving authenticated behavior.
- Modify `backend-mua/app/Http/Resources/TransactionResource.php` only if the public resource needs a safe subset not already available.
- Modify `backend-mua/tests/Feature/PublicBookingTest.php` with creation-token and public status tests.
- Create `backend-mua/tests/Feature/PublicBookingPaymentTest.php` for token security, schedule gating, Snap creation, and retry behavior.
- Modify `frontend-mua/src/api/bookingApi.js` with response normalization and public status/Snap requests.
- Modify `frontend-mua/src/pages/user/BookingPage.jsx` to retain the creation response and replace the success placeholder with polling/payment UI.
- Modify `frontend-mua/src/App.css` with focused status/payment styles.
- Modify `frontend-mua/index.html` only if Snap.js is loaded globally; otherwise load it lazily in the booking page.

## Task 1: Establish the public credential data model

**Files:**
- Create: `backend-mua/database/migrations/2026_08_15_000001_add_public_payment_access_to_bookings.php`
- Modify: `backend-mua/app/Models/Booking.php`
- Test: `backend-mua/tests/Feature/PublicBookingPaymentTest.php`

- [ ] **Step 1: Write failing model/schema tests**

Add tests asserting a public booking has a non-empty hashed credential, an expiry approximately 30 days after creation, and that the raw credential is not equal to the stored hash. Assert expired credentials fail verification and valid credentials pass.

```php
it('stores only a hashed expiring public payment credential', function () {
    $service = Service::factory()->create(['is_active' => true]);
    $response = $this->postJson('/api/bookings', publicBookingPayload($service))->assertCreated();

    $rawToken = $response->json('payment_access_token');
    $booking = Booking::findOrFail($response->json('id'));

    expect($rawToken)->toBeString()->not->toBeEmpty()
        ->and($booking->payment_access_token_hash)->not->toBe($rawToken)
        ->and($booking->payment_access_token_expires_at)->not->toBeNull()
        ->and($booking->hasValidPublicPaymentToken($rawToken))->toBeTrue();
});
```

- [ ] **Step 2: Run the focused test and verify it fails**

Run: `cd backend-mua && php artisan test tests/Feature/PublicBookingPaymentTest.php --filter="hashed expiring"`

Expected: FAIL because the migration, model fields, response token, and helper do not exist.

- [ ] **Step 3: Implement migration and model helpers**

Add nullable `payment_access_token_hash` with a unique index and `payment_access_token_expires_at` timestamp to `bookings`. Add the fields to `Booking` fillable/casts. Implement:

```php
public function hasValidPublicPaymentToken(string $token): bool
{
    return $this->payment_access_token_expires_at?->isFuture() === true
        && Hash::check($token, $this->payment_access_token_hash ?? '');
}
```

Keep the raw token out of the model and database.

- [ ] **Step 4: Run the focused model test**

Run: `cd backend-mua && php artisan test tests/Feature/PublicBookingPaymentTest.php --filter="hashed expiring"`

Expected: The test still fails only on token generation/response until Task 2 is complete; record the exact failure before continuing.

## Task 2: Return a public token from booking creation

**Files:**
- Modify: `backend-mua/app/Actions/Bookings/CreateBooking.php`
- Modify: `backend-mua/app/Http/Controllers/BookingController.php`
- Modify: `backend-mua/tests/Feature/PublicBookingTest.php`

- [ ] **Step 1: Add creation response assertions**

Extend the existing safe-default public booking test to assert `payment_access_token` is present in the response and that `starts_at` remains null. Add an assertion that a second serialization of the same booking does not include the raw token.

- [ ] **Step 2: Run the focused public booking tests**

Run: `cd backend-mua && php artisan test tests/Feature/PublicBookingTest.php --filter="safe defaults|token"`

Expected: FAIL because `CreateBooking` currently returns only a persisted booking.

- [ ] **Step 3: Generate and carry the raw token without persisting it**

Have `CreateBooking::handle()` return a small result object/array containing the booking and raw token, or attach a non-persisted runtime attribute to the returned booking. Use `Str::random(64)`, hash it with `Hash::make`, persist the hash and `now()->addDays(30)`, and ensure the controller adds only the raw token to the one-time creation response. Do not put it into `BookingResource` globally.

- [ ] **Step 4: Run public booking tests**

Run: `cd backend-mua && php artisan test tests/Feature/PublicBookingTest.php --filter="safe defaults|token"`

Expected: PASS.

## Task 3: Add narrow public status and Snap endpoints

**Files:**
- Create: `backend-mua/app/Http/Requests/PublicBookingRequest.php`
- Create: `backend-mua/app/Http/Resources/PublicBookingResource.php`
- Create: `backend-mua/app/Http/Controllers/PublicBookingController.php`
- Modify: `backend-mua/routes/api.php`
- Modify: `backend-mua/app/Actions/Transactions/CreateSnapTransaction.php`
- Create: `backend-mua/tests/Feature/PublicBookingPaymentTest.php`

- [ ] **Step 1: Write failing endpoint tests**

Cover these cases:

```php
it('returns only safe booking status with a valid token', function () {
    [$booking, $token] = createPublicBookingWithToken();

    $this->getJson("/api/public/bookings/{$booking->id}/status?token={$token}")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $booking->id)
        ->assertJsonMissingPath('data.client_phone')
        ->assertJsonMissingPath('data.activity_logs');
});

it('rejects missing invalid and expired public tokens', function () {
    [$booking, $token] = createPublicBookingWithToken();

    $this->getJson("/api/public/bookings/{$booking->id}/status?token=wrong")->assertUnauthorized();
    $booking->update(['payment_access_token_expires_at' => now()->subMinute()]);
    $this->getJson("/api/public/bookings/{$booking->id}/status?token={$token}")->assertUnauthorized();
});

it('does not create Snap before a booking is scheduled', function () {
    [$booking, $token] = createPublicBookingWithToken();

    $this->postJson("/api/public/bookings/{$booking->id}/transactions/snap?token={$token}")
        ->assertUnprocessable();
});
```

Use a fake `PaymentGateway` for successful Snap tests. Assert the public endpoint does not require `Authorization`.

- [ ] **Step 2: Run the endpoint tests and verify they fail**

Run: `cd backend-mua && php artisan test tests/Feature/PublicBookingPaymentTest.php`

Expected: FAIL because routes, token request validation, resource, and controller do not exist.

- [ ] **Step 3: Implement token validation and public resource**

Validate a required non-empty `token` query parameter. Resolve the booking by UUID, return `401` for missing/invalid/expired credentials, and apply `throttle:30,1` to status and Snap routes. Build `PublicBookingResource` with only ID, public status, requested date/end time, schedule, services, gross total, and latest payment status. Do not include phone, address, internal user, tasks, or activity logs.

- [ ] **Step 4: Implement public status controller method**

Load `bookingServices.service` and the latest transaction. Return the public resource with a `payment` object containing `transaction_status`, `gross_amount`, `snap_token` only when appropriate for the current client flow, and `redirect_url` only when needed. Never return the access token from this endpoint.

- [ ] **Step 5: Adapt Snap action for a public actor**

The current `CreateSnapTransaction` requires `User $actor` because it stores `transactions.user_id` and records activity. Use the existing seeded owner/system user as the internal actor for public payment creation, selected explicitly by a small resolver (for example, the first active owner), and preserve the authenticated controller’s `$request->user()` behavior. Do not make `transactions.user_id` nullable unless tests prove there is no stable system actor. Keep the existing terminal-booking and positive-total checks.

- [ ] **Step 6: Implement schedule-gated public Snap method**

Validate the token, reject cancelled/done/unscheduled bookings with `422`, call the existing Snap action with the system actor, and return `201` with a safe transaction payload. Make repeated requests return the existing active pending transaction for this booking rather than calling Midtrans a second time; add this idempotency check inside the transaction lock.

- [ ] **Step 7: Run public payment tests**

Run: `cd backend-mua && php artisan test tests/Feature/PublicBookingPaymentTest.php`

Expected: PASS, including unauthorized token cases, safe response shape, schedule gating, fake gateway invocation, and duplicate-request protection.

## Task 4: Add frontend API helpers and payment script loading

**Files:**
- Modify: `frontend-mua/src/api/bookingApi.js`
- Modify: `frontend-mua/src/api/client.js` only if GET query/error behavior needs adjustment

- [ ] **Step 1: Add API helper contract**

Implement helpers that preserve the backend paths and normalize response data at the page boundary:

```js
export function getPublicBookingStatus(id, token) {
  return apiClient.get(`/public/bookings/${encodeURIComponent(id)}/status?token=${encodeURIComponent(token)}`)
}

export function createPublicSnapTransaction(id, token) {
  return apiClient.post(`/public/bookings/${encodeURIComponent(id)}/transactions/snap?token=${encodeURIComponent(token)}`, {})
}
```

Keep the token out of authorization headers and do not log it.

- [ ] **Step 2: Add lazy Midtrans Snap loader**

Create a small module-level loader in `bookingApi.js` or a focused `frontend-mua/src/api/midtrans.js` that appends the sandbox Snap.js script once, resolves when `window.snap` exists, and rejects on script error. Use `VITE_MIDTRANS_CLIENT_KEY` and the sandbox URL by default; do not hardcode a server key.

- [ ] **Step 3: Run frontend lint/build before page edits**

Run: `cd frontend-mua && npm run lint && npm run build`

Expected: PASS, establishing a clean baseline for the API helper changes.

## Task 5: Replace the booking success placeholder with public tracking/payment UI

**Files:**
- Modify: `frontend-mua/src/pages/user/BookingPage.jsx`
- Modify: `frontend-mua/src/App.css`

- [ ] **Step 1: Add state and storage contract**

On successful creation, unwrap the response, save `{ bookingId, token }` under one namespaced local-storage key, and set the page to a public tracking state. Treat missing token/ID as an error instead of rendering a misleading success screen.

- [ ] **Step 2: Implement bounded polling with cleanup**

Add a `useEffect` that calls `getPublicBookingStatus` immediately, then every 10 seconds while the booking is `pending` or payment is not settled. Clear the interval on unmount, when the session changes, and when the booking reaches `cancelled`, `done`, or paid state. Retain the last successful status when a network call fails and expose a manual refresh button.

- [ ] **Step 3: Implement payment gating**

Render:

- `Menunggu konfirmasi jadwal` while `starts_at` or `ends_at` is absent.
- `Pembayaran tersedia` once the booking is scheduled and no settled payment exists.
- `Memproses verifikasi pembayaran` after Snap is opened or redirect is followed.
- `Pembayaran berhasil` when the public status endpoint reports settlement/capture with accepted fraud status and `paid_at`.
- An expired/invalid-token recovery action that clears the local session and lets the client submit a new booking.

The payment button must remain disabled until schedule availability is reported by the API.

- [ ] **Step 4: Integrate Snap.js and redirect fallback**

On payment click, call the public Snap endpoint. If the response has `snap_token` and `window.snap`, call `window.snap.pay(token, callbacks)`; otherwise navigate to `redirect_url`. Call status refresh after success/pending callbacks but never mark the booking paid solely from a browser callback.

- [ ] **Step 5: Add focused styles**

Add classes for booking ID, status alert, schedule summary, payment CTA, spinner, retry, and compact error state. Reuse existing design tokens and button classes; do not introduce a new component library.

- [ ] **Step 6: Run frontend validation**

Run: `cd frontend-mua && npm run lint && npm run build`

Expected: PASS.

## Task 6: Backend regression validation and documentation

**Files:**
- Modify: `backend-mua/tests/Feature/CreateSnapTransactionTest.php` only if idempotency changes require existing assertions.
- Modify: `README.md` or `backend-mua/README.md` only if the API documentation location is maintained there.

- [ ] **Step 1: Run focused backend tests**

Run: `cd backend-mua && php artisan test tests/Feature/PublicBookingTest.php tests/Feature/PublicBookingPaymentTest.php tests/Feature/CreateSnapTransactionTest.php`

Expected: PASS.

- [ ] **Step 2: Run backend style checks**

Run: `cd backend-mua && vendor/bin/pint --test`

Expected: PASS.

- [ ] **Step 3: Regenerate and inspect API documentation**

Run: `cd backend-mua && php artisan l5-swagger:generate`

Expected: OpenAPI generation succeeds and includes both public endpoints with token query parameters and `401`/`422` responses.

- [ ] **Step 4: Run the full available suites**

Run: `cd backend-mua && php artisan test` and `cd frontend-mua && npm run lint && npm run build`

Expected: Existing backend tests plus new public payment tests pass; frontend lint and build pass.

- [ ] **Step 5: Review the diff for secret leakage**

Run: `git --no-pager diff --check` and inspect that no Midtrans server key, raw token logging, or token serialization in public status responses is present.

Expected: No whitespace errors or credential leakage.
