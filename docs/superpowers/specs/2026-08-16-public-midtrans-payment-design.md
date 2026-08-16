# Public Midtrans Payment Design

## Goal

Add a public client payment page for scheduled bookings. Staff can send the
booking payment link through WhatsApp. The client opens the link, chooses a
down payment or final settlement, pays through Midtrans Snap, and sees the
booking status update after Midtrans confirms the payment.

## Payment Rules

- Payment is available only after `starts_at` and `ends_at` are assigned.
- The client can choose `dp` or `pelunasan`.
- Minimum DP is Rp50,000 when the booking total is below Rp500,000.
- Minimum DP is 10% of the booking total when the booking total is Rp500,000
  or more.
- A settlement payment equals the remaining unpaid amount.
- The backend calculates and validates every amount; the frontend only submits
  the selected payment type.
- A new transaction is created for each payment attempt that is not already
  represented by a pending or successful transaction for the same payment type.
- Successful payments are `capture` or `settlement` transactions with an
  accepted fraud status.

## Backend Design

The public booking payment endpoint accepts a payment type. It authorizes the
existing hashed public booking token, loads all booking transactions, and
calculates:

- booking total from booking services;
- successful amount from paid transactions;
- remaining amount as total minus successful amount;
- DP amount from the configured minimum DP rule;
- settlement amount from the remaining amount.

The endpoint rejects payments when the booking is terminal, the booking is not
scheduled, the requested payment type is unavailable, or no balance remains.
It returns an existing pending/paid transaction for the same payment type when
possible, otherwise it creates a new Snap transaction with `type` set to `dp`
or `pelunasan`.

The Midtrans webhook remains the source of truth for payment success. When a
transaction becomes successfully paid, the webhook records `paid_at` and
marks the booking `confirmed` when the booking is not terminal. Payment status
is exposed through the public booking status resource, including the latest
transaction, total paid amount, remaining amount, and available payment
choices without exposing private client data.

## Frontend Design

Add a public payment page under the user pages. The page receives the booking
ID and public access token from the WhatsApp link, then loads the public status
endpoint. It displays:

- booking schedule and selected services;
- total service amount;
- amount already paid;
- remaining balance;
- DP minimum and settlement amount;
- a `Bayar DP` action when DP is available;
- a `Bayar pelunasan` action when a balance remains.

The payment page loads Midtrans Snap only when the client starts payment. It
shows loading, configuration, unavailable-payment, cancelled-payment, and
success states. After Snap closes, it refreshes the public booking status. It
also polls while the booking is awaiting webhook confirmation so the client
sees the confirmed state without manually refreshing.

The existing booking success/tracking flow should link to this page when the
booking is scheduled. The WhatsApp link format will contain the booking ID and
public access token, and must not contain an authenticated session token.

## Security and Error Handling

- Public endpoints continue to require the expiring hashed booking token.
- Payment amounts are never accepted from the browser.
- Duplicate clicks and retries reuse the matching pending or paid transaction.
- The backend prevents overpayment and settlement creation before the balance
  is available.
- Webhook processing remains idempotent.
- Public responses exclude client phone, address, activity logs, and internal
  user data.

## Testing

Add or update feature tests for:

- DP minimum below and above the Rp500,000 threshold;
- settlement amount after a paid DP;
- rejection of invalid payment types and unavailable payments;
- separate DP and settlement transactions;
- public token authorization and safe response fields;
- webhook payment success changing the booking to `confirmed`;
- repeated requests reusing the matching transaction;
- frontend lint and production build.
