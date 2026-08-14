# Booking Navigation Loading Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add immediate loading feedback and prevent duplicate navigation interactions in the booking form.

**Architecture:** Keep the behavior local to `BookingPage.jsx` with one `navigationLoading` state and a helper that schedules the state reset after the UI transition. Reuse the existing `.spinner` CSS class and preserve submit loading behavior.

**Tech Stack:** React 19, JSX, existing Tailwind/CSS styles, Vite.

---

### Task 1: Add navigation loading state

**Files:**
- Modify: `frontend-mua/src/pages/user/BookingPage.jsx`

- [ ] Add `navigationLoading` state beside existing form state.
- [ ] Add a small `finishNavigation` helper using `requestAnimationFrame` to clear loading after state changes.
- [ ] Set loading only after validation succeeds in `nextStep`.
- [ ] Set loading in back, quantity, and optional-toggle handlers.
- [ ] Disable affected controls while loading and show `Memproses...` on the next-step button.
- [ ] Reuse existing `.spinner` markup for visible loading feedback.

### Task 2: Validate

**Files:**
- No test files exist in the frontend package.

- [ ] Run `npm run lint` from `frontend-mua`.
- [ ] Run `npm run build` from `frontend-mua`.
- [ ] Confirm no unrelated files changed.
