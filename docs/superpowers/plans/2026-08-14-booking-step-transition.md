# Booking Step Transition Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Animate booking form steps horizontally according to navigation direction.

**Architecture:** Store a small `stepDirection` value in `BookingPage` and apply it to the form panel class. CSS handles the 280ms slide while the summary remains static and reduced-motion users skip the transform.

**Tech Stack:** React 19, CSS, Vite.

---

### Task 1: Add directional step animation

**Files:**
- Modify: `frontend-mua/src/pages/user/BookingPage.jsx`
- Modify: `frontend-mua/src/App.css`

- [ ] Add `stepDirection` state initialized to `forward`.
- [ ] Set `stepDirection` to `forward` before valid next-step navigation.
- [ ] Set `stepDirection` to `backward` before back navigation.
- [ ] Apply `step-transition` and the direction class to `.form-panel`.
- [ ] Add 280ms enter animations for forward and backward classes.
- [ ] Add reduced-motion rules that disable the animation.
- [ ] Run `npm run lint`, `npm run build`, and diagnostics for modified files.
