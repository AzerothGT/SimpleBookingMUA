# Home-to-Booking Transition

## Goal

Make the primary Home CTA feel intentional by using a subtle fade-and-slide-up transition before opening the booking page.

## Design

The Home page owns a `isLeaving` state. Clicking `Cek jadwal & ajukan booking` prevents immediate navigation, adds the `is-leaving` class to the page root, and navigates to `/booking` after 320ms. CSS animates the Home content with opacity and upward translation. Users who prefer reduced motion navigate immediately without the animation.

## Scope

Modify only `frontend-mua/src/pages/user/Home.jsx` and `frontend-mua/src/App.css`. Add no dependencies and do not change BookingPage behavior.

## Validation

Run `npm run lint` and `npm run build` from `frontend-mua`.
