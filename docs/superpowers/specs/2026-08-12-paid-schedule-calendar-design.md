# Paid Schedule Calendar Design

## Goal

Replace the native date input in booking step 2 with an always-visible monthly calendar that marks finalized, paid schedules while keeping the entire desktop booking flow within the viewport.

## User Flow

1. The client opens booking step 2 and sees an inline monthly calendar.
2. Dates before today are disabled.
3. Dates containing finalized, paid schedules have an orange availability marker but remain selectable.
4. Selecting a date updates `client_requested_date` and shows the finalized busy time ranges for that date.
5. The client enters only the proposed end time. The client never enters an actual start time.
6. Staff or owner later assigns the actual `starts_at` and `ends_at` values. Existing backend overlap validation remains responsible for preventing staff schedule conflicts.

## Calendar Data Rules

A booking appears in the public calendar only when all of these conditions are true:

- `bookings.status` is `confirmed`.
- `bookings.starts_at` and `bookings.ends_at` are present.
- The booking has an accepted transaction whose `transaction_status` is `capture` or `settlement`.
- The accepted transaction has `fraud_status = accept` and `paid_at` is present.

Pending proposals, unpaid bookings, cancelled bookings, and records without a final schedule do not appear.

## Backend API

Add a public endpoint:

```text
GET /api/schedule/calendar?from=YYYY-MM-DD&to=YYYY-MM-DD
```

The date range is required and limited to one calendar month. The response groups finalized busy ranges by date:

```json
{
  "data": [
    {
      "date": "2026-08-12",
      "busy_ranges": [
        {
          "starts_at": "2026-08-12T09:00:00+07:00",
          "ends_at": "2026-08-12T12:00:00+07:00"
        }
      ]
    }
  ]
}
```

The existing single-date schedule endpoint should apply the same finalized-and-paid filter so both public availability views stay consistent.

## Frontend Architecture

- Install `react-day-picker` and use it for the always-visible calendar.
- Add a focused calendar component under `frontend-mua/src/components` so `BookingPage.jsx` keeps form orchestration rather than calendar rendering details.
- Fetch calendar data when the displayed month changes.
- Convert busy dates into a `react-day-picker` modifier for orange markers.
- Keep the selected date in the existing `form.date` field using `YYYY-MM-DD`.
- Show loading and failure states without blocking date selection. If monthly availability fails to load, explain that availability could not be displayed and allow the proposal to continue.
- Display the selected date's busy ranges beside the calendar. Busy dates remain selectable because the actual start time is assigned later by staff or owner.

## Layout

On desktop, step 2 uses a compact two-column form area:

- Left: inline calendar.
- Right: selected date, busy ranges, proposed end-time input, and schedule status.
- Existing booking summary remains in the outer right column.
- Navigation actions remain visible at the bottom of the form area.

For desktop viewports above `760px`, calendar dimensions, gaps, headings, and controls are constrained so step 2 fits inside the available booking shell without document scrolling at the reference viewport. Internal scrolling remains a fallback only for unusually short viewports or expanded error content.

At `760px` and below, the calendar and controls stack vertically and normal document scrolling remains enabled.

## Validation And Safety

- Validate `from` and `to` as dates, require `to >= from`, and reject ranges longer than one month.
- Do not expose client names, phone numbers, addresses, staff identities, or booking IDs in the public calendar response.
- Keep staff schedule overlap validation in `AssignBookingSchedule`; the client form does not attempt to infer a start time or guarantee availability.
- Add backend feature tests proving that only confirmed, paid, accepted, scheduled bookings appear.
- Add frontend lint and production build validation.
- Verify the calendar can be navigated and dates can be selected using keyboard controls provided by `react-day-picker`.
