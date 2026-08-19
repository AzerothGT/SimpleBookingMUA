# Midtrans Snap Payment Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the existing public Midtrans Snap popup flow with correct payment typing, explicit environment selection, resilient frontend behavior, and focused tests.

**Architecture:** Keep Snap token creation and payment state on the Laravel backend. `PaymentPage` requests a server-created token, opens the Snap popup, and refreshes the backend status after browser callbacks; only verified webhook/backend state determines payment completion.

**Tech Stack:** Laravel/Pest, React/Vite, Midtrans Snap JS, existing `PaymentGateway` abstraction.

---

### Task 1: Cover the repayment type regression

**Files:**
- Modify: `backend-mua/tests/Feature/PublicBookingPaymentTest.php`
- Modify: `backend-mua/app/Actions/Transactions/CreateSnapTransaction.php`

- [ ] **Step 1: Add a failing public repayment test**

Add a test after the existing public Snap creation test. Create a scheduled booking, bind a fake `PaymentGateway`, request `type=pelunasan`, and assert the JSON transaction type is `pelunasan`:

```php
it('stores public settlement payments as pelunasan', function () {
    [$booking, $token] = publicPaymentBooking();
    $booking->update([
        'status' => 'confirmed',
        'starts_at' => '2026-08-10 12:00:00',
        'ends_at' => '2026-08-10 15:00:00',
    ]);

    app()->bind(PaymentGateway::class, fn () => new class implements PaymentGateway
    {
        public function createSnap(Booking $booking, string $orderId, int $grossAmount): array
        {
            return ['token' => 'settlement-token', 'redirect_url' => 'https://example.test/pay'];
        }
    });

    $this->postJson("/api/public/bookings/{$booking->id}/transactions/snap?token={$token}&type=pelunasan")
        ->assertCreated()
        ->assertJsonPath('type', 'pelunasan');
});
```

- [ ] **Step 2: Run the focused test and verify failure**

Run from `backend-mua`:

```bash
php artisan test tests/Feature/PublicBookingPaymentTest.php --filter="stores public settlement payments as pelunasan"
```

Expected: FAIL because `CreateSnapTransaction` currently persists `'type' => 'dp'`.

- [ ] **Step 3: Persist the requested type**

In `CreateSnapTransaction::handle`, change the transaction assignment from:

```php
'type' => 'dp',
```

to:

```php
'type' => $type,
```

- [ ] **Step 4: Run the focused test and verify success**

Run the same focused Pest command. Expected: PASS.

### Task 2: Make Snap environment selection explicit

**Files:**
- Modify: `frontend-mua/src/api/bookingApi.js`
- Modify: `frontend-mua/.env.example` if the file exists; otherwise document the existing variable in `frontend-mua/README.md`

- [ ] **Step 1: Confirm current frontend environment convention**

Inspect `frontend-mua/.env.example`, Vite config, and existing `VITE_` variables. Preserve the current client-key name and add only a boolean-like environment switch if no equivalent exists.

- [ ] **Step 2: Select the Snap script host from configuration**

Use the existing sandbox-safe behavior by default, while allowing production to be enabled explicitly:

```js
const isProduction = import.meta.env.VITE_MIDTRANS_IS_PRODUCTION === 'true'
const snapHost = isProduction ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com'
script.src = `${snapHost}/snap/snap.js`
```

Do not expose or read the server key in frontend code.

- [ ] **Step 3: Verify the loader behavior statically**

Run frontend lint and inspect the generated source/config to confirm only `VITE_MIDTRANS_CLIENT_KEY` and the explicit production switch are used by the loader.

### Task 3: Harden PaymentPage Snap UX

**Files:**
- Modify: `frontend-mua/src/pages/user/PaymentPage.jsx`

- [ ] **Step 1: Preserve backend-driven callback semantics**

Keep all Snap callbacks mapped to `refresh`, but wrap the callback refresh in a stable helper that clears the payment loading state immediately when Snap closes or returns a result. Do not mark the booking paid from `onSuccess`.

- [ ] **Step 2: Handle unavailable Snap JS with redirect fallback**

When `loadMidtransSnap()` fails after the backend returns a valid `redirect_url`, assign `window.location` to that URL instead of leaving the customer with an opaque loader error. If neither token nor redirect URL exists, show the existing safe error.

- [ ] **Step 3: Keep errors safe and actionable**

Use the backend payload message when available, otherwise the request error message, and preserve `refresh` as the only status source. Ensure `paymentLoading` is cleared when token creation or Snap opening fails.

- [ ] **Step 4: Run frontend validation**

From `frontend-mua` run:

```bash
npm run lint
npm run build
```

Expected: zero lint warnings/errors and a successful Vite build.

### Task 4: Run payment regression suite

**Files:**
- No new files.

- [ ] **Step 1: Run all focused backend payment tests**

From `backend-mua` run:

```bash
php artisan test tests/Feature/PublicBookingPaymentTest.php tests/Feature/CreateSnapTransactionTest.php tests/Feature/MidtransPaymentGatewayTest.php
```

Expected: all selected tests pass.

- [ ] **Step 2: Inspect the final diff**

Run:

```bash
git diff --check
git status --short
```

Expected: no whitespace errors; only the approved Snap changes, spec, and plan are present.
