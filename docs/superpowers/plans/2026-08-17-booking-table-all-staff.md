# Booking Table All Staff Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tampilkan semua nama staf yang dijadwalkan pada kolom Staff tabel booking admin, dengan fallback untuk data lama dan booking tanpa staf.

**Architecture:** Tambahkan formatter data murni di modul kecil agar aturan pemilihan nama staf dapat diuji tanpa memasang framework test baru. `BookingsPage` menggunakan formatter tersebut saat merender kolom Staff.

**Tech Stack:** React 19, JavaScript ES modules, Node.js built-in test runner, Vite, Oxlint

---

### Task 1: Staff Name Formatter

**Files:**
- Create: `frontend-mua/src/pages/admin/bookingStaff.js`
- Create: `frontend-mua/src/pages/admin/bookingStaff.test.js`

- [ ] **Step 1: Write the failing test**

```js
import test from 'node:test'
import assert from 'node:assert/strict'
import { formatBookingStaff } from './bookingStaff.js'

test('formats every scheduled staff name', () => {
  const booking = {
    staff_schedules: [
      { staff: { name: 'Staff One' } },
      { staff: { name: 'Staff Two' } },
    ],
  }

  assert.equal(formatBookingStaff(booking), 'Staff One, Staff Two')
})

test('falls back to legacy staff', () => {
  assert.equal(formatBookingStaff({ staff: { name: 'Staff Lama' } }), 'Staff Lama')
})

test('ignores unnamed schedules and shows the unassigned label', () => {
  assert.equal(formatBookingStaff({ staff_schedules: [{ staff: null }] }), 'Belum ditugaskan')
})
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `node --test src/pages/admin/bookingStaff.test.js`
Expected: FAIL because `bookingStaff.js` does not exist.

- [ ] **Step 3: Write the minimal implementation**

```js
export const formatBookingStaff = (booking) => {
  const scheduledNames = (booking.staff_schedules ?? [])
    .map((schedule) => schedule.staff?.name)
    .filter(Boolean)

  return scheduledNames.join(', ') || booking.staff?.name || 'Belum ditugaskan'
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `node --test src/pages/admin/bookingStaff.test.js`
Expected: 3 tests pass.

### Task 2: Booking Table Integration

**Files:**
- Modify: `frontend-mua/src/pages/admin/BookingsPage.jsx:1-76`

- [ ] **Step 1: Import the formatter**

```js
import { formatBookingStaff } from './bookingStaff'
```

- [ ] **Step 2: Use it in the Staff column**

Replace the existing Staff renderer with:

```js
{ key: 'staff', label: 'Staff', render: (row) => formatBookingStaff(row) },
```

- [ ] **Step 3: Run focused and project validation**

Run: `node --test src/pages/admin/bookingStaff.test.js && npm run lint && npm run build`
Expected: all formatter tests pass, lint exits successfully, and Vite completes the production build.
