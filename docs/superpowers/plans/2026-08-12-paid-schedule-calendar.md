# Paid Schedule Calendar Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the native booking date input with an inline monthly calendar that marks only confirmed, paid schedules and preserves the client-only proposed end-time workflow.

**Architecture:** Laravel exposes a public monthly schedule endpoint filtered to confirmed bookings with accepted paid transactions. React consumes that endpoint in a focused `BookingCalendar` component built with `react-day-picker`; `BookingPage` keeps form state and displays the selected date's finalized busy ranges. Desktop step 2 uses a compact two-column layout while mobile stacks normally.

**Tech Stack:** Laravel 13, Pest 5, React 19, react-day-picker, Tailwind CSS 4, Vite 8

---

### Task 1: Define and test the public monthly calendar API

**Files:**
- Create: `backend-mua/app/Http/Requests/ScheduleCalendarRequest.php`
- Modify: `backend-mua/routes/api.php`
- Modify: `backend-mua/app/Http/Controllers/BookingController.php`
- Modify: `backend-mua/tests/Feature/PublicBookingTest.php`

- [ ] **Step 1: Add failing feature tests**

Add tests that create bookings covering confirmed+settled, confirmed+pending payment, pending+settled, cancelled+settled, and out-of-range cases. Assert `GET /api/schedule/calendar?from=2026-08-01&to=2026-08-31` returns only the confirmed booking with an accepted `capture` or `settlement` transaction and non-null `paid_at`, grouped as:

```json
{
  "data": [
    {
      "date": "2026-08-10",
      "busy_ranges": [
        {
          "starts_at": "2026-08-10T10:00:00.000000Z",
          "ends_at": "2026-08-10T12:00:00.000000Z"
        }
      ]
    }
  ]
}
```

Also assert invalid or over-one-month ranges return validation errors.

- [ ] **Step 2: Run the targeted test and confirm it fails**

Run: `php artisan test tests/Feature/PublicBookingTest.php`

Expected: FAIL because `/api/schedule/calendar` does not exist.

- [ ] **Step 3: Add request validation**

Create `ScheduleCalendarRequest` with public authorization and rules:

```php
return [
    'from' => ['required', 'date'],
    'to' => ['required', 'date', 'after_or_equal:from', 'before_or_equal:'.Carbon::parse($this->from)->addMonth()->toDateString()],
];
```

Use an `after` validator if needed to avoid parsing invalid input before base validation completes.

- [ ] **Step 4: Implement the route and controller query**

Add:

```php
Route::get('/schedule/calendar', [BookingController::class, 'calendar']);
```

Query confirmed bookings with final schedules in the requested range and require an accepted, paid `capture` or `settlement` transaction through `whereHas('transactions', ...)`. Select only `starts_at` and `ends_at`, order chronologically, group by `starts_at->toDateString()`, and return the public data contract without IDs or personal fields.

Update `checkAvailability` to use the same confirmed+paid transaction filter.

- [ ] **Step 5: Run targeted backend tests**

Run: `php artisan test tests/Feature/PublicBookingTest.php`

Expected: PASS.

### Task 2: Add the calendar dependency and API client

**Files:**
- Modify: `frontend-mua/package.json`
- Modify: `frontend-mua/bun.lock`
- Modify: `frontend-mua/src/api/bookingApi.js`

- [ ] **Step 1: Install react-day-picker**

Run: `bun add react-day-picker`

Expected: `react-day-picker` appears in dependencies and the Bun lockfile updates.

- [ ] **Step 2: Add the monthly API function**

Add:

```js
export function getScheduleCalendar(from, to) {
  return apiClient.get(`/schedule/calendar?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`)
}
```

### Task 3: Build the inline booking calendar

**Files:**
- Create: `frontend-mua/src/components/BookingCalendar.jsx`
- Modify: `frontend-mua/src/pages/user/BookingPage.jsx`
- Modify: `frontend-mua/src/App.css`

- [ ] **Step 1: Create `BookingCalendar`**

Use `DayPicker` in `single` mode with Indonesian locale, `startMonth` set to today, disabled dates before today, month navigation, and a `busy` modifier based on API dates. Fetch the visible month using `getScheduleCalendar`, expose selection through `onSelectDate(yyyyMmDd)`, and show a compact legend for available, busy, and selected states.

- [ ] **Step 2: Replace the native date field**

In booking step 2, replace `<input type="date">` and the manual `Cek jadwal` button with `BookingCalendar`. Keep `form.date` as `YYYY-MM-DD`, preserve validation messages, and set `schedule` from the selected date's `busy_ranges` automatically.

Do not add a start-time input. Keep the existing proposed end-time input and submission payload unchanged.

- [ ] **Step 3: Display selected-date busy ranges**

Update `ScheduleStatus` so a selected busy date lists localized start-end times, while empty dates show that no finalized schedule is recorded. API failure should state that availability could not be displayed but must not block the proposal.

- [ ] **Step 4: Add compact responsive calendar styles**

Style `react-day-picker` through scoped `.booking-calendar` CSS variables and selectors. On desktop, use a two-column `.date-step-layout` for calendar and time/status controls with stable day-cell dimensions that fit the reference viewport. At `760px` and below, stack the sections and preserve document scrolling.

### Task 4: Verify backend and frontend

**Files:**
- Verify all modified files

- [ ] **Step 1: Run backend formatting check**

Run: `vendor/bin/pint --test`

Expected: PASS.

- [ ] **Step 2: Run backend feature tests**

Run: `php artisan test tests/Feature/PublicBookingTest.php`

Expected: PASS.

- [ ] **Step 3: Run frontend lint**

Run: `bun run lint`

Expected: 0 errors and 0 warnings.

- [ ] **Step 4: Run frontend production build**

Run: `bun run build`

Expected: successful Vite build.

- [ ] **Step 5: Review editor diagnostics**

Confirm no new JSX or PHP errors. Tailwind at-rule warnings from the generic CSS language server are non-blocking when the Tailwind build succeeds.
