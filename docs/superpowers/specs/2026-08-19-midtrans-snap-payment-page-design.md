# Midtrans Snap Payment Page Design

## Goal

Complete the existing public Midtrans Snap popup integration for `PaymentPage` without moving provider calls into the browser or trusting browser callbacks as payment proof.

## Architecture

- The frontend requests a Snap token from the existing public booking endpoint.
- The backend creates and persists the Snap transaction with the requested payment type (`dp` or `pelunasan`).
- The frontend opens Snap popup with the returned token and refreshes public booking status after every Snap callback.
- Payment fulfillment remains backend-driven through verified Midtrans notifications and persisted transaction state.
- The existing `redirect_url` remains a fallback when Snap JS cannot be loaded or a token is unavailable.

## Changes

- Fix transaction persistence so `CreateSnapTransaction` stores the requested type instead of hardcoding `dp`.
- Make Snap JS environment selection explicit through the frontend environment configuration while preserving sandbox as the safe default.
- Harden `PaymentPage` error handling and loading behavior around token creation, Snap loading, and popup callbacks.
- Add backend coverage for public settlement and repayment type behavior, including idempotent reuse of existing attempts.

## Boundaries and assumptions

- No Midtrans credentials are added to source control.
- The server key remains backend-only; only the Snap client key is exposed to the frontend.
- The dashboard Payment Notification URL must be configured to the public HTTPS webhook endpoint `/api/webhooks/midtrans`.
- Sandbox-to-production verification requires merchant credentials, activated methods, and dashboard access; local tests do not prove a live provider transaction.

## Validation

- Run the focused backend payment feature tests.
- Run frontend lint.
- Run frontend production build.
