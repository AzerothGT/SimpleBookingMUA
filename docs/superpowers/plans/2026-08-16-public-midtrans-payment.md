# Public Midtrans Payment Implementation Plan

> **For agentic workers:** Implement task-by-task with verification checkpoints.

**Goal:** Support public Midtrans Snap payments for DP and settlement, with backend-calculated amounts and automatic booking confirmation after successful payment.

**Architecture:** Extend the existing public booking token endpoints and transaction model. Each payment type gets its own transaction, while webhook success updates the booking and the public status resource exposes payment summary. Add a dedicated public React payment page linked from the existing booking tracking flow.

**Tech Stack:** Laravel 13, Pest, React 19, React Router, Midtrans Snap.

---

### Task 1: Backend payment calculation and public endpoint

**Files:**
- Modify: `backend-mua/app/Http/Controllers/PublicBookingController.php`
- Modify: `backend-mua/app/Http/Resources/PublicBookingResource.php`
- Modify: `backend-mua/app/Actions/Transactions/CreateSnapTransaction.php`
- Test: `backend-mua/tests/Feature/PublicBookingPaymentTest.php`

- [ ] Add tests for DP thresholds, settlement after paid DP, distinct transaction types, and invalid payment choices.
- [ ] Extend public Snap endpoint with `type=dp|pelunasan` and calculate amounts from booking services plus paid transactions.
- [ ] Reuse pending/paid transaction for the requested type; create a new Snap transaction with the calculated amount and type otherwise.
- [ ] Expose total, paid, remaining, and payment type/amount through the public resource.
- [ ] Run targeted Pest tests.

### Task 2: Confirm booking after successful webhook payment

**Files:**
- Modify: `backend-mua/app/Actions/Transactions/HandleMidtransWebhook.php`
- Test: `backend-mua/tests/Feature/PublicBookingPaymentTest.php`

- [ ] Add a webhook regression test proving accepted `capture`/`settlement` marks the transaction paid and booking `confirmed`.
- [ ] Update webhook handling transactionally and idempotently without changing refund behavior.
- [ ] Run the targeted payment tests and Pint on changed PHP files.

### Task 3: Public payment page and route

**Files:**
- Create: `frontend-mua/src/pages/user/PaymentPage.jsx`
- Modify: `frontend-mua/src/App.jsx`
- Modify: `frontend-mua/src/api/bookingApi.js`
- Modify: `frontend-mua/src/pages/user/BookingPage.jsx`

- [ ] Add a public `/payment/:bookingId` route that reads `token` from the query string.
- [ ] Render booking schedule, totals, paid amount, remaining balance, DP and settlement actions.
- [ ] Start Snap with only the selected payment type, then poll public status after closing.
- [ ] Link scheduled booking tracking to the payment page.
- [ ] Run frontend lint and production build.

### Task 4: Final verification

- [ ] Run targeted backend payment tests.
- [ ] Run frontend lint and build.
- [ ] Run `git diff --check` and inspect the final diff for unrelated changes.
