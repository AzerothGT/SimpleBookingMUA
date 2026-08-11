# Desktop Booking Viewport Fit Design

## Goal

Make the booking page fit within one desktop or laptop viewport without a document-level scrollbar, while preserving normal scrolling on mobile.

## Scope

- Apply only to the `/booking` page at viewport widths above `760px`.
- Keep the mobile layout and document scrolling unchanged.
- Do not change booking behavior, form validation, data flow, or visual content.

## Layout

- Add a booking-page wrapper class to the page root.
- On desktop, make that wrapper a vertical layout with a height of `100svh` and hidden document overflow.
- Keep the navbar at its natural height.
- Let `.booking-shell` fill the remaining height instead of using `min-height: 100svh`.
- Hide the booking footer on desktop because the form is the primary full-screen experience.
- Allow `.booking-shell` to scroll internally only when a longer step, validation message, or unusually short viewport cannot fit. The normal first step should not show a scrollbar at the target viewport shown in the reference screenshot.

## Responsive Behavior

At `760px` and below, restore the current flow layout: the page height is content-driven, the footer remains visible, and the browser document may scroll normally.

## Validation

- Build and lint the frontend.
- Check `/booking` at the screenshot-sized desktop viewport and confirm no document scrollbar appears on step 1.
- Check a mobile viewport and confirm content remains reachable through normal scrolling.
- Check longer booking steps to ensure no content is clipped; internal shell scrolling may appear when required.
