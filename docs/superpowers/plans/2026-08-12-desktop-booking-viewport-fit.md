# Desktop Booking Viewport Fit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fit the desktop booking page within one viewport without clipping longer booking steps or changing mobile scrolling.

**Architecture:** Add a page-specific root class in the React page, then use a desktop media query to create a `100svh` vertical layout. The booking shell fills the remaining space and owns overflow only when content exceeds the available height; mobile keeps the existing document flow.

**Tech Stack:** React 19, Tailwind CSS 4 utilities in CSS, Vite 8

---

### Task 1: Add the booking page layout hook

**Files:**
- Modify: `frontend-mua/src/pages/user/BookingPage.jsx:212`

- [ ] **Step 1: Add a page-specific class to the root element**

Change the normal booking page root from:

```jsx
<main>
```

to:

```jsx
<main className="booking-page">
```

The success page remains unchanged because it already has its own full-screen layout.

### Task 2: Fit the desktop page to the viewport

**Files:**
- Modify: `frontend-mua/src/App.css:62,150-153`

- [ ] **Step 1: Remove the unconditional viewport minimum from the shell**

Set `.booking-shell` to `min-height: 0` so the desktop parent can control its available height without adding a second viewport height.

- [ ] **Step 2: Add desktop-only viewport layout rules**

Inside `@media (min-width: 761px)`, add:

```css
.booking-page {
  display: flex;
  height: 100svh;
  flex-direction: column;
  overflow: hidden;
}

.booking-page .booking-shell {
  width: 100%;
  min-height: 0;
  flex: 1;
  overflow-y: auto;
}

.booking-page .site-footer {
  display: none;
}
```

This keeps the navbar at natural height, allocates the remaining height to the form, and provides internal scrolling only for longer steps or short screens.

- [ ] **Step 3: Preserve mobile behavior**

Keep the existing `@media (max-width: 760px)` booking shell and footer rules unchanged. The desktop rules do not apply at that width, so the document remains content-driven and scrollable.

### Task 3: Validate the result

**Files:**
- Verify: `frontend-mua/src/pages/user/BookingPage.jsx`
- Verify: `frontend-mua/src/App.css`

- [ ] **Step 1: Run lint**

Run: `bun run lint`

Expected: exit code `0` with no lint errors caused by the change.

- [ ] **Step 2: Run production build**

Run: `bun run build`

Expected: exit code `0` and generated Vite production assets.

- [ ] **Step 3: Inspect diagnostics**

Check project diagnostics for the modified files and resolve any new errors.
