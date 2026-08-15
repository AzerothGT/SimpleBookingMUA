# Admin CRUD Pages Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build API-connected booking, service, and read-only activity log pages under the existing admin sidebar.

**Architecture:** Add one shared admin shell plus small table, drawer, modal, and confirmation primitives. Keep API calls in `adminApi.js`; each page owns its filters, selected record, form state, and refresh behavior. Register nested admin routes without changing public routes.

**Tech Stack:** React 19, React Router, Phosphor Icons, existing `apiClient`, Tailwind-generated CSS plus `App.css`, Vite, oxlint.

---

## Files and Responsibilities

- Create `frontend-mua/src/api/adminApi.js`: booking/service/activity API adapters and response unwrapping.
- Create `frontend-mua/src/components/AdminLayout.jsx`: shared sidebar shell and page header.
- Create `frontend-mua/src/components/AdminDataTable.jsx`: loading, error, empty, and responsive table wrapper.
- Create `frontend-mua/src/components/AdminDrawer.jsx`: accessible right-side detail/edit panel.
- Create `frontend-mua/src/components/ConfirmDialog.jsx`: destructive-action confirmation dialog.
- Create `frontend-mua/src/pages/admin/BookingsPage.jsx`: booking list, filters, detail drawer, edit/status/assign/delete mutations.
- Create `frontend-mua/src/pages/admin/ServicesPage.jsx`: service list and create/edit/delete modal flows.
- Create `frontend-mua/src/pages/admin/ActivityLogsPage.jsx`: read-only filtered activity list and detail drawer.
- Modify `frontend-mua/src/App.jsx`: add `/admin/bookings`, `/admin/services`, and `/admin/activity` routes.
- Modify `frontend-mua/src/App.css`: shared admin CRUD layout, tables, drawer, forms, dialog, and responsive rules.

## Task 1: Create API adapters

**Files:**
- Create: `frontend-mua/src/api/adminApi.js`

- [ ] **Step 1: Add response unwrapping helper**

Implement `unwrap(payload)` returning `payload?.data ?? payload`, and `unwrapList(payload)` returning `Array.isArray(payload) ? payload : payload?.data ?? []`. Laravel pagination responses must work without exposing pagination details to page components.

- [ ] **Step 2: Add booking adapters**

Implement:

```js
export const listAdminBookings = (filters = {}) => apiClient.get(`/bookings?${new URLSearchParams(filters)}`)
export const getAdminBooking = (id) => apiClient.get(`/bookings/${id}`)
export const updateAdminBooking = (id, body) => apiClient.post(`/bookings/${id}?_method=PATCH`, body)
export const deleteAdminBooking = (id) => apiClient.post(`/bookings/${id}?_method=DELETE`, {})
export const changeAdminBookingStatus = (id, status) => apiClient.post(`/bookings/${id}/status`, { status }, { headers: { 'Content-Type': 'application/json', 'X-HTTP-Method-Override': 'PATCH' } })
export const assignAdminBooking = (id, body) => apiClient.post(`/bookings/${id}/assign-staff`, body)
```

Because the existing client only exposes GET/POST, use Laravel method override for PATCH/DELETE. Export `unwrap` and `unwrapList` for page components.

- [ ] **Step 3: Add service and activity adapters**

Implement list/create/update/delete service functions using method override for PATCH/DELETE, plus `listActivityLogs(filters)` and `getActivityLog(id)`. Do not add activity mutations.

- [ ] **Step 4: Run lint**

```bash
cd frontend-mua
npm run lint
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add frontend-mua/src/api/adminApi.js
git commit -m "Add admin CRUD API adapters"
```

## Task 2: Build shared admin primitives

**Files:**
- Create: `frontend-mua/src/components/AdminLayout.jsx`
- Create: `frontend-mua/src/components/AdminDataTable.jsx`
- Create: `frontend-mua/src/components/AdminDrawer.jsx`
- Create: `frontend-mua/src/components/ConfirmDialog.jsx`

- [ ] **Step 1: Implement `AdminLayout`**

Accept `title`, `eyebrow`, `description`, and `children`. Read `demo_role` from localStorage, pass it to `DashboardSidebar`, persist role changes, and render the existing sidebar beside a page content region with a page heading and refresh/action slot.

- [ ] **Step 2: Implement `AdminDataTable`**

Accept `columns`, `rows`, `isLoading`, `error`, `emptyMessage`, and `onRetry`. Render a semantic table with each column `{ key, label, render }`. Render loading, error with retry button, and empty states. Use `data-label` on cells for mobile styling.

- [ ] **Step 3: Implement `AdminDrawer`**

Accept `open`, `title`, `onClose`, and `children`. Render nothing when closed; otherwise render a fixed overlay and `role="dialog"` panel with `aria-modal="true"`, close button, backdrop close, Escape key close, and focus-visible controls.

- [ ] **Step 4: Implement `ConfirmDialog`**

Accept `open`, `title`, `message`, `confirmLabel`, `isLoading`, `onCancel`, `onConfirm`. Render a native-style dialog overlay with cancel and destructive confirm buttons. The confirm button must be disabled while loading.

- [ ] **Step 5: Run lint**

```bash
cd frontend-mua
npm run lint
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add frontend-mua/src/components/AdminLayout.jsx frontend-mua/src/components/AdminDataTable.jsx frontend-mua/src/components/AdminDrawer.jsx frontend-mua/src/components/ConfirmDialog.jsx
git commit -m "Add shared admin CRUD components"
```

## Task 3: Implement bookings page

**Files:**
- Create: `frontend-mua/src/pages/admin/BookingsPage.jsx`

- [ ] **Step 1: Load and filter booking list**

On mount and refresh, call `listAdminBookings` with `status`, `client_name`, and `client_phone` query values. Use a 300ms debounce for text search. Missing token should show a link to `/login`; `401` should remove stale auth and show the same login action; other errors must retain existing rows and show retry.

- [ ] **Step 2: Implement table columns and selection**

Show client, requested date/end time, services, assigned staff, status badge, and a `Detail` action. Clicking a row action loads `getAdminBooking(id)` into the detail drawer.

- [ ] **Step 3: Implement booking detail drawer**

Show immutable requested fields, client contact/address, services with quantity and subtotal, current status, staff/schedule, notes, tasks, and transactions. Add controls for:

- Edit `client_address`, `maps_url`, `maps_lat`, `maps_lng`, `notes` with `updateAdminBooking`.
- Change status with `changeAdminBookingStatus`.
- Assign `user_id`, `starts_at`, `ends_at` with `assignAdminBooking`.
- Delete after `ConfirmDialog` using `deleteAdminBooking`.

Do not send client requested date/time, `user_id`, `starts_at`, or `ends_at` to generic update.

- [ ] **Step 4: Handle mutation feedback**

Show inline success/error messages in the drawer. On success refresh the list and reload the selected booking. On `422`, map `error.payload.errors` below relevant fields; otherwise show the API message.

- [ ] **Step 5: Run lint**

```bash
cd frontend-mua
npm run lint
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add frontend-mua/src/pages/admin/BookingsPage.jsx
git commit -m "Add admin booking CRUD page"
```

## Task 4: Implement services page

**Files:**
- Create: `frontend-mua/src/pages/admin/ServicesPage.jsx`

- [ ] **Step 1: Load service list**

Call `listAdminServices` on mount and refresh. Show name, formatted IDR price, active/inactive text status, image count, and action buttons.

- [ ] **Step 2: Implement create/edit modal**

Use one form with `name`, numeric `price`, and `is_active`. Create uses `createAdminService`; edit uses `updateAdminService`. Validate required name and non-negative price client-side, but display server `422` errors too.

- [ ] **Step 3: Implement delete confirmation**

Open `ConfirmDialog` with service name. On confirm call `deleteAdminService`, close the modal, and refresh. Keep the list when deletion fails and show the API message.

- [ ] **Step 4: Run lint**

```bash
cd frontend-mua
npm run lint
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add frontend-mua/src/pages/admin/ServicesPage.jsx
git commit -m "Add admin services CRUD page"
```

## Task 5: Implement activity log page

**Files:**
- Create: `frontend-mua/src/pages/admin/ActivityLogsPage.jsx`

- [ ] **Step 1: Load filtered logs**

Call `listActivityLogs` with `entity_type`, `action`, and `booking_id`. Provide native filter controls and a refresh button. Show actor, action, entity type, detail, and formatted timestamp.

- [ ] **Step 2: Implement read-only detail drawer**

Clicking a row calls `getActivityLog(id)` and opens `AdminDrawer`. Render actor, timestamp, action, entity IDs, detail, and `meta` with `JSON.stringify(meta, null, 2)`. Render no edit/delete controls.

- [ ] **Step 3: Run lint**

```bash
cd frontend-mua
npm run lint
```

Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add frontend-mua/src/pages/admin/ActivityLogsPage.jsx
git commit -m "Add activity log admin page"
```

## Task 6: Register routes and style CRUD pages

**Files:**
- Modify: `frontend-mua/src/App.jsx`
- Modify: `frontend-mua/src/App.css`

- [ ] **Step 1: Register routes**

Import pages and add:

```jsx
<Route path="/admin/bookings" element={<BookingsPage />} />
<Route path="/admin/services" element={<ServicesPage />} />
<Route path="/admin/activity" element={<ActivityLogsPage />} />
```

Keep `/admin` and all public routes unchanged.

- [ ] **Step 2: Add square admin CRUD styles**

Add styles for `.admin-page-shell`, `.admin-page-content`, `.admin-page-heading`, `.admin-toolbar`, `.admin-table`, `.admin-drawer`, `.admin-drawer-overlay`, `.admin-modal`, `.admin-form`, `.admin-field-error`, `.admin-alert`, `.admin-dialog`, `.admin-mobile-row`, and action buttons. Use existing theme tokens and no radius on primary containers.

- [ ] **Step 3: Add responsive rules**

At narrow widths, stack page headers/toolbars, make drawers full width, convert table cells to labeled blocks, and keep buttons at least 44px touch height. Do not introduce page-level horizontal scrolling.

- [ ] **Step 4: Run lint and build**

```bash
cd frontend-mua
npm run lint && npm run build
```

Expected: both PASS.

- [ ] **Step 5: Commit**

```bash
git add frontend-mua/src/App.jsx frontend-mua/src/App.css
git commit -m "Add admin CRUD routes and styles"
```

## Task 7: Final verification

**Files:**
- Modify only dashboard CRUD files if verification identifies a concrete issue.

- [ ] **Step 1: Run final frontend checks**

```bash
cd frontend-mua
npm run lint && npm run build
```

Expected: 0 lint errors and successful production bundle.

- [ ] **Step 2: Verify authenticated flows manually**

With a valid token, verify list/detail/filter/create/update/delete for services, list/detail/edit/status/assign/delete for bookings, and list/filter/detail read-only behavior for logs.

- [ ] **Step 3: Verify failure flows manually**

Remove `auth_token` and verify login prompts. Use invalid values to verify 422 feedback. Confirm destructive actions require confirmation and failed deletes preserve the list.

- [ ] **Step 4: Verify mobile and keyboard behavior**

Check drawer Escape/backdrop close, dialog cancel, focus-visible controls, mobile stacked rows, and no page-level horizontal overflow.

- [ ] **Step 5: Review diff**

```bash
git --no-pager diff --check
git --no-optional-locks status --short
```

Expected: no whitespace errors and only planned files changed.
