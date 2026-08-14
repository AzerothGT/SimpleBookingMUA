# Admin Dashboard Design

## Goal

Create a shared operational dashboard at `/admin` for admin, owner, and staff users. The dashboard should make the current operational state understandable within seconds and direct users toward bookings that need attention.

## Scope

The first version includes:

- A responsive dashboard layout with sidebar navigation and top bar.
- Four high-level metrics: bookings needing attention, today's agenda, confirmed bookings, and estimated revenue.
- Today's agenda for assigned or upcoming work.
- Recent bookings with status, date, client, service, amount, and staff.
- Status filtering and a refresh action.
- Role-aware display: admin/owner see all operational data; staff see assigned work when assignment data exists.
- Hybrid data loading: use authenticated API data when available, with fixture data as a usable fallback when authentication or the API is unavailable.
- Clear loading, fallback, and empty states.

The first version does not implement booking mutations, payment actions, user management, or a new booking-detail workflow. Those actions link to existing flows or remain visually represented without inventing unsupported API behavior.

## Visual Direction

Use a warm studio-control-room aesthetic consistent with the public booking experience:

- Cream background, deep green ink, and terracotta accents.
- Existing `Manrope` and `DM Mono` typography.
- Sidebar on desktop; compact horizontal navigation on narrow screens.
- Spacious 4-based spacing scale, restrained shadows, and strong grouping through proximity.
- Status is communicated through label text and color, never color alone.

The primary action is opening and following up on a booking that needs attention. Secondary navigation and metrics remain visually subordinate.

## Component Structure

- `AdminDashboard.jsx`: page container, data state, role state, status filter, refresh, and responsive composition.
- `dashboardData.js`: fixture records and normalization helpers with the shape consumed by the dashboard.
- `DashboardSidebar.jsx`: product navigation and role context.
- `MetricCard.jsx`: reusable metric summary.
- `AgendaCard.jsx`: today's work list and empty/loading states.
- `BookingTable.jsx`: recent booking list with responsive card treatment.
- `StatusBadge.jsx`: accessible status presentation.

The dashboard route is added in `App.jsx`. Existing public routes remain unchanged.

## Data Flow

1. The page reads the current token and attempts to request protected booking/user data.
2. A successful response is normalized to the dashboard view model.
3. If no token exists or the request fails, the fixture view model is shown with a non-blocking fallback indicator.
4. Admin and owner views show all records. Staff views filter records by assignment when an authenticated user or fixture role is staff.
5. Refresh repeats the request and preserves a usable view during failure.

The API adapter must remain small and use the existing `apiClient` conventions. No new dependency is required.

## Interaction and Accessibility

- Status filter uses a labeled native select or equivalent keyboard-accessible control.
- Refresh is a real button with a visible busy state.
- Navigation has a current-page indicator and accessible labels.
- Every status badge includes visible text.
- Tables/lists remain readable and usable on mobile without horizontal scrolling where possible.
- Reduced-motion preferences continue to be respected by the existing global CSS.

## Validation

Run:

```bash
npm run lint
npm run build
```

Manually verify `/admin` at wide and narrow viewports, including role switch/demo state, status filtering, refresh fallback, sidebar navigation, empty states, and readable status distinctions.
