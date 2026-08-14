# Admin Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a responsive shared admin/staff dashboard at `/admin` with live API loading, fixture fallback, role-aware records, metrics, agenda, filters, and refresh behavior.

**Architecture:** Keep the page container in `AdminDashboard.jsx`, isolate fixture/normalization logic in `dashboardData.js`, and split visual units into sidebar, metric, agenda, booking list, and status badge components. Use the existing `apiClient` and add only a protected booking-list adapter; if the API is unavailable, render the same normalized view model from fixture data.

**Tech Stack:** React 19, React Router, Phosphor Icons, existing CSS in `App.css`, Vite, oxlint.

---

## Files and Responsibilities

- Create `frontend-mua/src/pages/admin/AdminDashboard.jsx`: dashboard state, API/fallback loading, role selection, filtering, metrics, and composition.
- Create `frontend-mua/src/pages/admin/dashboardData.js`: fixture records, labels, date/price helpers, and response normalization.
- Create `frontend-mua/src/pages/admin/components/DashboardSidebar.jsx`: desktop sidebar and mobile navigation.
- Create `frontend-mua/src/pages/admin/components/MetricCard.jsx`: one metric card with icon, value, and supporting trend/caption.
- Create `frontend-mua/src/pages/admin/components/AgendaCard.jsx`: today agenda list and empty state.
- Create `frontend-mua/src/pages/admin/components/BookingTable.jsx`: responsive recent-booking table/list.
- Create `frontend-mua/src/pages/admin/components/StatusBadge.jsx`: accessible status label.
- Modify `frontend-mua/src/api/bookingApi.js`: add `listBookings()`.
- Modify `frontend-mua/src/App.jsx`: register `/admin` before the catch-all route.
- Modify `frontend-mua/src/App.css`: add dashboard-specific layout, component, state, and responsive styles without changing public-page selectors.

## Task 1: Add dashboard data model and API adapter

**Files:**
- Create: `frontend-mua/src/pages/admin/dashboardData.js`
- Modify: `frontend-mua/src/api/bookingApi.js`

- [ ] **Step 1: Define the normalized record shape and fixtures**

Create `dashboardData.js` with records containing `id`, `clientName`, `serviceName`, `date`, `time`, `status`, `amount`, `staffName`, and `staffId`; export `dashboardFixture`, `statusLabels`, `formatCurrency`, `formatDashboardDate`, and `normalizeBookings`.

Fixture data must include at least one record for each `pending`, `confirmed`, `done`, and `cancelled`, plus two records for today's agenda. Use fixed ISO dates relative to the current dashboard date helper so the agenda remains demonstrable.

- [ ] **Step 2: Add the protected list adapter**

Append this function to `bookingApi.js`:

```js
export function listBookings() {
  return apiClient.get('/bookings')
}
```

- [ ] **Step 3: Run lint to catch syntax and import errors**

Run from `frontend-mua`:

```bash
npm run lint
```

Expected: command succeeds; no dashboard files should report errors.

- [ ] **Step 4: Commit the data layer**

```bash
git add frontend-mua/src/pages/admin/dashboardData.js frontend-mua/src/api/bookingApi.js
git commit -m "Add dashboard data model"
```

## Task 2: Build reusable dashboard components

**Files:**
- Create: `frontend-mua/src/pages/admin/components/DashboardSidebar.jsx`
- Create: `frontend-mua/src/pages/admin/components/MetricCard.jsx`
- Create: `frontend-mua/src/pages/admin/components/AgendaCard.jsx`
- Create: `frontend-mua/src/pages/admin/components/BookingTable.jsx`
- Create: `frontend-mua/src/pages/admin/components/StatusBadge.jsx`

- [ ] **Step 1: Implement `StatusBadge`**

Render a `<span>` with `status-badge status-badge--{status}`, visible Indonesian status text from `statusLabels`, and `aria-label={`Status ${label}`}`. Unknown statuses must fall back to the raw status string.

- [ ] **Step 2: Implement `MetricCard`**

Accept `label`, `value`, `caption`, and `icon`. Render an article with a decorative icon wrapper, an eyebrow label, a large value, and muted caption. The icon must be `aria-hidden`.

- [ ] **Step 3: Implement `DashboardSidebar`**

Accept `role` and `onRoleChange`. Render brand text, navigation links for `Dashboard`, `Booking`, `Layanan`, and `Aktivitas`, plus role selector options `admin`, `owner`, and `staff`. Use `NavLink` for `/admin`, `/admin/bookings`, `/admin/services`, and `/admin/activity`. Use visible text for the current role and a logout link styled as secondary; do not add logout behavior in this phase.

- [ ] **Step 4: Implement `AgendaCard`**

Accept `items`, `isLoading`, and `onOpenBooking`. Render a section with heading `Agenda hari ini`, a loading message when loading, an empty message when no items exist, and compact agenda rows otherwise. Each row shows time, client, service, staff, and a text button calling `onOpenBooking(item)`.

- [ ] **Step 5: Implement `BookingTable`**

Accept `items`, `isLoading`, `onOpenBooking`. Render a section with heading `Booking terbaru`, a semantic table on wide screens, and CSS-driven stacked rows on small screens. Columns: client, service, date, staff, amount, status, action. Show loading and empty states. Use `StatusBadge` and a real button for `Lihat detail`.

- [ ] **Step 6: Commit the reusable components**

```bash
git add frontend-mua/src/pages/admin/components
git commit -m "Build dashboard display components"
```

## Task 3: Compose the dashboard page and route

**Files:**
- Create: `frontend-mua/src/pages/admin/AdminDashboard.jsx`
- Modify: `frontend-mua/src/App.jsx`

- [ ] **Step 1: Implement dashboard loading and fallback behavior**

In `AdminDashboard.jsx`, initialize `records` from `dashboardFixture`, `role` from `localStorage.getItem('demo_role') || 'admin'`, `statusFilter` as `all`, `isLoading` as `false`, and `isFallback` as `true`.

Create `loadDashboard()` that sets loading, calls `listBookings()` only when `auth_token` exists, normalizes `payload.data ?? payload`, and replaces fixture data only when the normalized response is non-empty. On missing token, failed request, or empty response, preserve fixtures and set fallback true. Always clear loading in `finally`.

Call `loadDashboard()` once in `useEffect`. The refresh button calls it again. Role changes update `demo_role` and filter staff records by `staffId` when role is `staff`; admin and owner retain all records.

- [ ] **Step 2: Compute metrics and filtered records**

Use `useMemo` for role-filtered records and status-filtered records. Compute:

```js
const metrics = {
  attention: records.filter((item) => item.status === 'pending').length,
  today: records.filter((item) => item.date === todayKey).length,
  confirmed: records.filter((item) => item.status === 'confirmed').length,
  revenue: records
    .filter((item) => item.status !== 'cancelled')
    .reduce((sum, item) => sum + item.amount, 0),
}
```

Derive agenda from today’s role-filtered records and recent bookings from the status-filtered role records, limited to six items.

- [ ] **Step 3: Compose layout and interactions**

Render the sidebar, main top bar with page title, current formatted date, role label, and refresh button, a fallback notice when `isFallback`, four `MetricCard`s, an agenda/quick-action row, a status filter control, and `BookingTable`.

`onOpenBooking` should use `window.alert` with the booking client and id for this phase; it must not invent a route or mutation endpoint. The quick action should link to `/booking` only as a temporary public booking link if retained; do not present it as an admin mutation.

- [ ] **Step 4: Register the route**

In `App.jsx`, import `AdminDashboard` and add:

```jsx
<Route path="/admin" element={<AdminDashboard />} />
```

Place it before the wildcard route. Existing routes must remain unchanged.

- [ ] **Step 5: Run lint**

```bash
cd frontend-mua
npm run lint
```

Expected: PASS with no errors.

- [ ] **Step 6: Commit the page and route**

```bash
git add frontend-mua/src/pages/admin/AdminDashboard.jsx frontend-mua/src/App.jsx
 git commit -m "Add admin dashboard route"
```

## Task 4: Add dashboard styling and responsive behavior

**Files:**
- Modify: `frontend-mua/src/App.css`

- [ ] **Step 1: Add desktop layout tokens and shell styles**

Append dashboard selectors using existing variables where available. Add styles for `.admin-page`, `.admin-shell`, `.dashboard-sidebar`, `.dashboard-main`, `.dashboard-topbar`, `.dashboard-heading`, `.dashboard-actions`, `.dashboard-metrics`, `.dashboard-grid`, `.dashboard-panel`, and `.dashboard-notice`.

Use a two-column desktop shell with a fixed-width sidebar, cream background, deep green ink, terracotta accent, rounded panels, and consistent `rem` spacing. Do not alter public page selectors.

- [ ] **Step 2: Add component styles**

Style `.metric-card`, `.metric-card-icon`, `.metric-value`, `.agenda-list`, `.agenda-item`, `.booking-table-wrap`, `.booking-table`, `.status-badge`, `.status-badge--pending`, `.status-badge--confirmed`, `.status-badge--done`, `.status-badge--cancelled`, `.dashboard-filter`, `.dashboard-empty`, `.dashboard-loading`, and `.dashboard-action`.

Ensure status colors have sufficient contrast and each status remains understandable through its text label.

- [ ] **Step 3: Add mobile styles**

Within the existing mobile media query or a new `@media (max-width: 900px)`, make `.admin-shell` one column, convert the sidebar navigation to a horizontal scroll-safe row, stack metric cards into two columns then one column under `520px`, stack dashboard panels, and transform table rows into block cards without horizontal overflow.

- [ ] **Step 4: Run lint and build**

```bash
cd frontend-mua
npm run lint
npm run build
```

Expected: both commands succeed and Vite emits a production bundle.

- [ ] **Step 5: Commit styling**

```bash
git add frontend-mua/src/App.css
git commit -m "Style responsive admin dashboard"
```

## Task 5: Verify behavior and polish

**Files:**
- Modify only files that fail verification; do not change unrelated public pages.

- [ ] **Step 1: Verify production build and lint again**

```bash
cd frontend-mua
npm run lint && npm run build
```

Expected: both commands pass.

- [ ] **Step 2: Manually verify desktop behavior**

Open `/admin` at a wide viewport and check:

- Four metrics render with fixture data.
- Fallback notice appears when no `auth_token` exists.
- `pending`, `confirmed`, `done`, and `cancelled` labels are visible.
- Status filter changes the booking list and metrics remain understandable.
- Role selector changes `staff` to assigned records and `admin`/`owner` to all records.
- Refresh preserves usable data when the API is unavailable.
- Sidebar links have a current-page indicator.

- [ ] **Step 3: Manually verify mobile behavior**

At a narrow viewport, check that navigation, metric cards, agenda, filter, and booking rows remain readable, have usable touch targets, and do not require horizontal page scrolling.

- [ ] **Step 4: Fix only verified dashboard issues**

If a check fails, make the smallest dashboard-only correction, then rerun `npm run lint && npm run build`.

- [ ] **Step 5: Review the final diff**

```bash
git --no-pager diff HEAD~4..HEAD --stat
git --no-pager status --short
```

Expected: only the dashboard files, API adapter, route, design spec, and implementation plan are changed; no unrelated user-page files are modified.
