# Copy Booking ID Design

## Goal

Add a clear action button beside the public booking ID on the submitted-booking screen so clients can copy the ID for later reference.

## Behavior

- Render a `Copy ID` button beside the booking ID.
- Use `navigator.clipboard.writeText(bookingId)` on click.
- Change the label to `Tersalin` for approximately two seconds after success.
- Keep the booking ID visible and selectable even if clipboard access fails.
- Show an accessible error message when copying fails.
- Add an accessible button label describing the copied value.

## Scope

- Modify only the submitted-booking UI in `frontend-mua/src/pages/user/BookingPage.jsx` and its related styles in `frontend-mua/src/App.css` if needed.
- No backend, API, dependency, or routing changes.
- Preserve the existing Indonesian UI language and visual tokens.

## Validation

- Run frontend lint and production build.
- Verify keyboard activation, success feedback, and failure feedback manually in a secure browser context.
