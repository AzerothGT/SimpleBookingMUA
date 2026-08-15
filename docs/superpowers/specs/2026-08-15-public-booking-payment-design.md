# Public Booking Payment Flow Design

## Goal

Align the public React booking flow with the Laravel API while enabling a client without an account to pay later through Midtrans after staff confirms a schedule.

## Approved Flow

1. A client submits a public booking.
2. The API creates a `pending` booking and returns a time-limited payment access token in the creation response. The token is issued once, then reused as a bearer credential until expiry.
3. The frontend stores the token locally and shows the booking ID plus a waiting-for-confirmation state.
4. The frontend polls a public booking status endpoint at a bounded interval and also provides manual refresh.
5. Once staff assigns `starts_at` and `ends_at`, the client can request a Snap transaction using the access token.
6. The frontend opens Midtrans Snap when a `snap_token` is available, or follows `redirect_url` when Snap.js is unavailable.
7. Payment status is ultimately sourced from the backend webhook; the frontend displays an intermediate verification state after payment is initiated.

## API Changes

### Booking creation response

`POST /api/bookings` continues accepting the existing public payload:

- `services[{id, qty}]`
- `client_name`
- `client_phone`
- `client_address`
- optional map fields and notes
- `client_requested_date`
- `client_requested_end_time`

The response additionally contains `payment_access_token`. The token is random, returned only in the creation response, stored hashed, and expires after 30 days. It is reused for public status polling and Snap creation until expiry. The booking response keeps `starts_at` null until internal scheduling.

### Public status endpoint

`GET /api/public/bookings/{booking}/status?token=...`

The endpoint validates the token and expiry, applies rate limiting, and returns only public-safe data:

- booking ID and public status
- requested date/end time
- assigned start/end time when available
- selected services and calculated total
- payment state and latest public transaction fields

It does not expose audit logs, internal user data, or administrative fields not needed by the client.

### Public Snap endpoint

`POST /api/public/bookings/{booking}/transactions/snap?token=...`

The endpoint validates the token and expiry, applies rate limiting, and calls the existing idempotent Snap transaction action. It rejects requests until the booking has an assigned schedule and meets the backend payment eligibility rules. The response returns the public transaction data needed by the frontend: `snap_token`, `redirect_url`, order ID, amount, and transaction status.

## Security

- The booking UUID is not treated as a credential.
- Access tokens are stored as hashes and compared securely.
- Tokens expire after 30 days and are not exposed in Midtrans URLs.
- Public responses are deliberately narrower than authenticated resources.
- Status and Snap endpoints are rate-limited.
- Snap creation remains idempotent so retries do not create duplicate active transactions.

## Frontend Changes

- Extend `bookingApi.js` with public status and Snap request functions.
- Normalize Laravel resource responses consistently (`data` versus direct JSON).
- Store the returned access token and booking ID after successful submission.
- Replace the success placeholder in `BookingPage.jsx` with a public booking status/payment state.
- Poll status at a bounded interval, stop polling on terminal payment/booking states, and keep a manual refresh action.
- Disable payment until the backend reports an assigned schedule.
- Load Midtrans Snap.js only when needed; use `snap_token` first and `redirect_url` as a fallback.
- Show clear states for pending schedule, payment available, payment initiated, paid, expired, and errors.
- Preserve a fallback link to restart or submit another booking.

## Error Handling

- Validation errors remain field-level where possible.
- Invalid or expired access tokens clear the stored public booking session and show a recovery message.
- `404`/`410`-style public access failures do not reveal whether an unrelated booking exists.
- Network failures retain the last known status and offer retry rather than discarding the booking session.
- Midtrans loading or popup failures show a retry action and do not mark payment as complete locally.

## Testing and Validation

Backend tests cover token hashing/expiry, unauthorized public access, safe response shape, schedule gating, and idempotent Snap creation. Frontend tests or focused build validation cover creation response normalization, status polling cleanup, payment gating, Snap fallback behavior, and error recovery. Run the frontend lint/build and the relevant backend test suite before completion.

## Scope Exclusions

This change does not add client accounts, public booking history across devices without the access token, WhatsApp/email notifications, refunds, or changes to staff/admin scheduling permissions.
