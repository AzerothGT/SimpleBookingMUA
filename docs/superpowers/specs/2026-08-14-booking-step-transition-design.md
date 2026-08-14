# Booking Step Transition

## Goal

Make movement between booking steps feel continuous while keeping the summary panel stable as an orientation anchor.

## Design

Track `stepDirection` in `BookingPage`. Valid forward navigation sets `forward`, and back navigation sets `backward`. Apply the matching class to the form panel so the next step enters from the right when moving forward and from the left when moving backward. The transition lasts 280ms. The summary panel remains outside the animated form panel. Reduced-motion users see no transform animation.

## Scope

Modify `frontend-mua/src/pages/user/BookingPage.jsx` and `frontend-mua/src/App.css`. Add no dependencies and preserve validation/loading behavior.

## Validation

Run `npm run lint`, `npm run build`, and diagnostics for the modified files.
