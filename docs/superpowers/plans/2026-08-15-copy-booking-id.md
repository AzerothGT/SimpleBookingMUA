# Copy Booking ID Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an accessible copy action beside the booking ID on the public submitted-booking screen.

**Architecture:** Keep the behavior local to `BookingPage.jsx`: copy the displayed booking ID with the browser Clipboard API, expose transient success/error state, and render a compact action beside the existing ID. Reuse the current CSS tokens and button styles without adding dependencies or backend changes.

**Tech Stack:** React 19, browser Clipboard API, Tailwind CSS via existing `App.css`, Vite.

---

### Task 1: Add copy booking ID interaction

**Files:**
- Modify: `frontend-mua/src/pages/user/BookingPage.jsx`
- Modify: `frontend-mua/src/App.css` only if the existing layout needs a small copy-action style

- [ ] **Step 1: Add copy state and handler**

Add a `copyState` state with `idle`, `success`, and `error` values. Implement a `copyBookingId` handler that calls `navigator.clipboard.writeText(publicSession.bookingId)`, sets `success` on completion, resets to `idle` after two seconds, and sets `error` when Clipboard API access fails. Do not modify the booking ID value.

```jsx
const [copyState, setCopyState] = useState('idle')

const copyBookingId = async () => {
  try {
    await navigator.clipboard.writeText(publicSession.bookingId)
    setCopyState('success')
    window.setTimeout(() => setCopyState('idle'), 2000)
  } catch {
    setCopyState('error')
  }
}
```

- [ ] **Step 2: Render the action beside the booking ID**

Replace the plain booking ID value in the submitted-booking status grid with a compact row containing the ID and a button:

```jsx
<div className="public-booking-id-row">
  <strong>{publicSession.bookingId}</strong>
  <button
    className="copy-booking-id"
    type="button"
    onClick={copyBookingId}
    aria-label={`Salin booking ID ${publicSession.bookingId}`}
  >
    {copyState === 'success' ? 'Tersalin' : 'Copy ID'}
  </button>
</div>
```

Render an accessible error message next to or below the action when `copyState === 'error'`, explaining that the ID can still be selected and copied manually.

- [ ] **Step 3: Add focused styles if needed**

Keep the existing status card layout intact. Add only styles needed to align the ID and action button, preserve wrapping for UUID values, and keep the button keyboard-focusable. Reuse existing `--ink`, `--muted`, `--line`, and button tokens.

- [ ] **Step 4: Run frontend validation**

Run:

```bash
cd frontend-mua && npm run lint && npm run build
```

Expected: lint reports zero warnings/errors and the production build succeeds.

- [ ] **Step 5: Manually verify the interaction**

Open the submitted-booking screen in a secure browser context and verify:

- The button is visible beside the booking ID.
- Mouse click and keyboard Enter/Space copy the exact ID.
- The label changes to `Tersalin` for about two seconds.
- Clipboard failure shows the fallback message.
- The ID remains readable and selectable on narrow screens.
