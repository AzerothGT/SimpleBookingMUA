# Booking Picker Popover Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Pisahkan pemilihan tanggal dan jam ke dua popover agar step 2 ringkas dan tidak mengalami layout aneh.

**Architecture:** `BookingPage` menyimpan satu state `openPicker` bernilai `date`, `time`, atau `null`. Dua trigger mengontrol popover masing-masing, sementara `useEffect` mendengarkan pointer down di luar wrapper step 2 untuk menutup popover. Komponen kalender dan analog picker tetap dipakai tanpa perubahan domain.

**Tech Stack:** React, CSS/Tailwind utilities, React Day Picker, Phosphor icons.

---

### Task 1: Add popover state and outside-click behavior

**Files:**
- Modify: `frontend-mua/src/pages/user/BookingPage.jsx:1-60`

- [ ] **Step 1: Add a ref and state**

Add `useRef` usage through the existing React import and create `openPicker` state with values `null`, `date`, or `time`.

- [ ] **Step 2: Close popovers on outside pointer down**

Register a `pointerdown` effect while a picker is open. If the event target is outside `.picker-popover-wrap`, call `setOpenPicker(null)`, and remove the listener during cleanup.

- [ ] **Step 3: Keep reset behavior deterministic**

Set `openPicker` to `null` inside `resetBooking`.

### Task 2: Replace step 2 with separate triggers and popovers

**Files:**
- Modify: `frontend-mua/src/pages/user/BookingPage.jsx:439-459`

- [ ] **Step 1: Render date trigger**

Render a button with `aria-expanded={openPicker === 'date'}` and a stable `aria-controls="booking-date-popover"`. Show the selected date or `Pilih tanggal`.

- [ ] **Step 2: Render date popover conditionally**

Render `BookingCalendar` only when `openPicker === 'date'`. On selection, update the date and set `openPicker(null)`.

- [ ] **Step 3: Render time trigger and popover conditionally**

Render time trigger when a date exists. Render `AnalogTimePicker` only when `openPicker === 'time'`; its `onChange` updates the time and closes the popover when the selected value changes.

- [ ] **Step 4: Preserve availability and errors**

Keep `CalendarAvailability` visible below the controls when a date exists and keep date/time error elements attached to their corresponding wrapper.

### Task 3: Style responsive picker controls

**Files:**
- Modify: `frontend-mua/src/App.css:138-181, 349-357`

- [ ] **Step 1: Replace large always-visible layout rules**

Use a two-column `.picker-controls` grid and `.picker-popover-wrap` relative positioning. Keep popovers absolutely positioned above the normal flow with a high z-index.

- [ ] **Step 2: Style triggers and popovers**

Give triggers equal minimum height, visible selected values, border/focus states, and popovers a paper background, border, padding, and shadow.

- [ ] **Step 3: Preserve picker dimensions inside popovers**

Keep the enlarged calendar and analog face dimensions while constraining popover width and allowing horizontal overflow only within the picker where required.

- [ ] **Step 4: Add mobile rules**

Switch controls to one column; make popovers static or full-width within the wrapper so they remain inside the viewport.

### Task 4: Validate the change

**Files:**
- No additional files.

- [ ] **Step 1: Run lint**

Run `npm run lint` in `frontend-mua`. Expected: zero warnings and errors.

- [ ] **Step 2: Run production build**

Run `npm run build` in `frontend-mua`. Expected: successful Vite build.

- [ ] **Step 3: Check patch formatting**

Run `git diff --check` from the repository root. Expected: no output and exit code 0.
