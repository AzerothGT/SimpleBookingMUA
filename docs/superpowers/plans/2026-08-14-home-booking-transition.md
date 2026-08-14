# Home-to-Booking Transition Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Animate the Home CTA with a subtle fade-and-slide-up transition before navigating to `/booking`.

**Architecture:** Keep transition state local to `Home.jsx`. The CTA handler starts the CSS animation and delays navigation for 320ms, while a reduced-motion media query skips the visual delay. No BookingPage or dependency changes.

**Tech Stack:** React 19, React Router, CSS, Vite.

---

### Task 1: Add CTA transition state and navigation

**Files:**
- Modify: `frontend-mua/src/pages/user/Home.jsx`

- [ ] Import `useState` from React and use `useNavigate` from `react-router-dom`.
- [ ] Add `isLeaving` state initialized to `false`.
- [ ] Add `handleBookingClick` that prevents default, immediately navigates when reduced motion is preferred, otherwise sets `isLeaving` and navigates after 320ms.
- [ ] Attach `className={isLeaving ? 'is-leaving' : ''}` to the Home root.
- [ ] Attach `onClick={handleBookingClick}` and `aria-disabled={isLeaving}` to the booking link.

### Task 2: Add transition styles

**Files:**
- Modify: `frontend-mua/src/App.css`

- [ ] Add `.home-page` transition styles for opacity and upward translation.
- [ ] Add `.home-page.is-leaving` with `opacity: 0`, `transform: translateY(-1.5rem)`, and pointer-event blocking.
- [ ] Add a `prefers-reduced-motion: reduce` rule that disables transition and transform.
- [ ] Keep the existing Home layout unchanged when not leaving.

### Task 3: Validate

**Files:**
- No test files exist in the frontend package.

- [ ] Run `npm run lint` from `frontend-mua` and confirm no errors.
- [ ] Run `npm run build` from `frontend-mua` and confirm success.
- [ ] Run diagnostics for `Home.jsx` and `App.css`.
