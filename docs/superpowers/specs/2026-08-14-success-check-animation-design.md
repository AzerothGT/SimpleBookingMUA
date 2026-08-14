# Success Check Animation

## Goal

Make the booking success confirmation feel clear and rewarding without delaying or distracting the client.

## Design

Replace the Phosphor icon inside `.success-mark` with an inline SVG check path. CSS animates the success mark with a short scale/fade entrance, then draws the check using `stroke-dasharray` and `stroke-dashoffset`. The animation is decorative and remains hidden from assistive technology through the existing `aria-hidden` attribute.

Users with `prefers-reduced-motion: reduce` see the completed mark immediately without animation.

## Scope

Modify `frontend-mua/src/pages/user/BookingPage.jsx` and `frontend-mua/src/App.css`. Add no dependencies and preserve existing success-state behavior.

## Validation

Run `npm run lint` and `npm run build` from `frontend-mua`, and check diagnostics for both modified files.
