# Admin CRUD Pages Design

## Goal

Add API-connected admin pages for bookings, services, and activity logs, matching the existing `DashboardSidebar` navigation and square visual theme.

## Scope

- `/admin/bookings`: booking table with status/search filters and drawer-based detail/edit actions.
- `/admin/services`: service table with create/edit modal and delete confirmation.
- `/admin/activity`: read-only activity log table with filters and detail drawer.
- Shared admin page shell using the existing sidebar and role selector.
- API-only data flow when authenticated; clear loading, empty, unauthorized, validation, and server-error states.
- Responsive desktop table and mobile stacked records.

Activity logs are intentionally read-only because the backend exposes only list/detail endpoints and audit records must remain immutable.

## Booking Behavior

The booking list loads from `GET /bookings` and supports status, client-name, and client-phone filters. The detail drawer loads `GET /bookings/{booking}` and displays client data, selected services, immutable requested date/end time, address, notes, status, assigned staff, schedule, tasks, and transactions when present.

Supported mutations:

- `PATCH /bookings/{booking}` for `client_address`, `maps_url`, `maps_lat`, `maps_lng`, and `notes`.
- `PATCH /bookings/{booking}/status` for valid state transitions.
- `POST /bookings/{booking}/assign-staff` for staff and schedule assignment.
- `DELETE /bookings/{booking}` after explicit confirmation.

No client-requested date/time or staff/schedule fields are sent through the generic update endpoint because the backend prohibits them there.

## Service Behavior

The service page loads active services from `GET /services`. Create, update, and delete use the authenticated service resource endpoints:

- `POST /services` with `name`, `price`, `is_active`.
- `PATCH /services/{service}` with editable service fields.
- `DELETE /services/{service}` after explicit confirmation.

The initial page does not manage service images; that remains a separate follow-up surface.

## Activity Behavior

The activity page loads `GET /activity-logs` and supports the backend filters `entity_type`, `action`, and `booking_id`. A detail drawer loads `GET /activity-logs/{activity_log}` and shows actor, timestamp, action, entity identifiers, detail, and formatted metadata. No mutation controls are rendered.

## Architecture

- `AdminLayout.jsx`: shared sidebar shell and page heading region.
- `AdminDataTable.jsx`: table/loading/empty/error primitives.
- `AdminDrawer.jsx`: accessible detail/edit side panel.
- `ConfirmDialog.jsx`: reusable destructive-action confirmation.
- `BookingsPage.jsx`, `ServicesPage.jsx`, `ActivityLogsPage.jsx`: page state and API orchestration.
- `adminApi.js`: API adapters and payload helpers for all three resources.

Existing dashboard components and public pages remain unchanged except for route registration and shared import usage where necessary.

## Visual and Accessibility Direction

- Use existing `--paper`, `--surface`, `--ink`, `--green`, `--lime`, `--orange`, `--line`, and typography tokens.
- Keep primary containers square, consistent with the dashboard revision.
- Use visible status text in addition to color.
- Use native labels, keyboard-accessible buttons, focus-visible states, and `role="dialog"` drawers.
- Preserve the list context when opening a drawer or modal.
- On mobile, stack fields and convert table rows into readable cards without page-level horizontal scrolling.

## Error Handling

- Missing token: show an authentication message with a link to `/login`.
- `401`: clear stale auth and show login action.
- `422`: show field-level or server validation errors in the active form.
- Other failures: show retryable page or drawer error without discarding existing list data.
- Delete requires confirmation and refreshes the list after success.

## Validation

Run:

```bash
npm run lint
npm run build
```

Manually verify authenticated list/detail/create/update/delete flows, filters, status transition, staff assignment, activity read-only behavior, API errors, confirmation dialogs, keyboard interaction, and narrow viewport layout.
