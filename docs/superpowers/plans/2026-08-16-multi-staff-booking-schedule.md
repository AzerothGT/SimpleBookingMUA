# Multi-Staff Booking Schedule Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow one booking to assign multiple staff with independent start times and one shared end time.

**Architecture:** Add a `booking_staff_schedules` relation keyed by booking and user. Each row stores `starts_at`; the booking remains the source of the shared `ends_at`. Keep legacy booking schedule columns for compatibility and mirror the first assigned staff into them. The admin drawer sends an array of staff assignments and displays one start-time input per staff.

**Tech Stack:** Laravel, Eloquent, Pest, React, CSS.

---

### Backend

- Add migration and `BookingStaffSchedule` model.
- Update assignment request/action/controller/resource to accept `ends_at` plus `staff[]`.
- Validate active staff, unique assignments, end time after every start, and overlap per staff.
- Eager-load assignments in admin resources and update schedule calendar queries to use assignments.
- Preserve legacy fields using the first assignment for compatibility.
- Add feature coverage for multiple staff and different start times.

### Frontend

- Replace single `staffId`/`startsAt` state with assignment rows.
- Render one staff selector and start-time input per row plus add/remove controls.
- Keep one shared end-time input.
- Submit `staff[]` and `ends_at`; do not render controls for role `staff`.
- Keep responsive drawer layout and preserve existing save/payment/delete actions.

### Validation

- Backend targeted schedule tests and Pint.
- Frontend lint, build, and diff check.
