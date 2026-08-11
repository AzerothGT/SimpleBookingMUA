# Tailwind CSS Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert the frontend's component styling from `App.css` selectors to Tailwind utility classes while preserving the existing booking UI and behavior.

**Architecture:** Keep `index.css` for Tailwind import, fonts, global reset, tokens, and reduced-motion rules. Move component layout and state styling into `className` strings in `App.jsx`. Keep `App.css` only for artwork pseudo-elements, custom orbit/card geometry, spinner animation, and any CSS that cannot be expressed clearly with utilities.

**Tech Stack:** React 19, Vite 8, Tailwind CSS 4, `@tailwindcss/vite`, CSS modules-free global styles.

---

## File Map

- Modify `frontend-mua/src/App.jsx`: apply Tailwind utilities to header, hero, booking, success, and footer markup without changing state or event logic.
- Modify `frontend-mua/src/App.css`: delete migrated selectors; retain only custom artwork, spinner keyframes, and minimal justified selectors.
- Modify `frontend-mua/src/index.css`: keep Tailwind import, font import, global reset, and reduced-motion behavior; add shared tokens only if needed by retained custom CSS.
- Validate `frontend-mua/package.json`, `frontend-mua/vite.config.js`, and `frontend-mua/bun.lock`: no dependency/config changes are expected during the migration.

### Task 1: Establish the migration guard

**Files:**
- Test: inline source assertions run from `SimpleBookingMUA`
- Modify: `frontend-mua/src/App.jsx`
- Modify: `frontend-mua/src/App.css`

- [ ] **Step 1: Record the current validation baseline**

Run:

```sh
bun run --cwd frontend-mua lint
bun run --cwd frontend-mua build
```

Expected: lint reports 0 warnings and 0 errors; Vite build completes successfully.

- [ ] **Step 2: Add a failing migration assertion**

Run this from the project root before changing component classes:

```sh
node -e "const fs=require('fs'); const jsx=fs.readFileSync('frontend-mua/src/App.jsx','utf8'); const css=fs.readFileSync('frontend-mua/src/App.css','utf8'); if (!jsx.includes('className=\"flex')) throw new Error('RED: App.jsx has no migrated Tailwind utility class'); if (!css.includes('.hero-section')) throw new Error('RED: expected source selector missing before migration'); console.log('PASS')"
```

Expected: FAIL because the current component does not yet contain the migration guard class and `App.css` still owns the hero selector.

### Task 2: Migrate shared shell, header, hero, and footer

**Files:**
- Modify: `frontend-mua/src/App.jsx:228-248,323`
- Modify: `frontend-mua/src/App.css:14-44,117`

- [ ] **Step 1: Convert the header and footer layout**

Use utilities for flex alignment, max width, padding, typography, colors, links, and responsive footer stacking. Preserve the existing `brand`, `location-label`, `header-cta`, and footer text content/classes where custom CSS still needs hooks.

- [ ] **Step 2: Convert hero layout and copy**

Apply utilities for the two-column desktop grid, mobile block layout, max widths, spacing, heading scale, body line-height, CTA placement, and note styling. Keep `hero-art` as a custom CSS hook because its pseudo-elements and geometry remain in `App.css`.

- [ ] **Step 3: Convert hero art container utilities**

Move background, height, overflow, position, minimum width, and rotation to JSX utilities. Retain `.hero-art::before`, `.hero-art::after`, `.art-orbit`, `.orbit-two`, `.art-card`, and `.art-caption` only where they define custom artwork.

- [ ] **Step 4: Run lint and build**

Expected: both commands pass; the hero and shell selectors are now removable without affecting layout.

### Task 3: Migrate booking shell, progress, form, summary, and actions

**Files:**
- Modify: `frontend-mua/src/App.jsx:250-321`
- Modify: `frontend-mua/src/App.css:46-116, mobile booking rules`

- [ ] **Step 1: Convert booking shell and heading**

Move max width, border, surface background, responsive padding, scroll margin, heading typography, and step counter utilities into JSX.

- [ ] **Step 2: Convert progress navigation**

Use Tailwind grid, border, spacing, mono typography, active color, and responsive column behavior. Keep the existing conditional `active` class logic and add utilities alongside it so step state remains unchanged.

- [ ] **Step 3: Convert service and date steps**

Migrate service list grid, option states, radio accent, selected/hover borders, date row layout, field labels, input styles, check button, schedule status, and selected summary. Preserve the existing `selected`, `idle`, `empty`, `busy`, and `error` class hooks until their custom selectors are removed.

- [ ] **Step 4: Convert detail and review steps**

Migrate field rows, grouped fieldsets, legends, review rows, optional labels, errors, and responsive stacking. Keep the semantic `fieldset`, `legend`, `label`, input, textarea, and ARIA structure unchanged.

- [ ] **Step 5: Convert summary and actions**

Apply utilities to the summary panel, pending pill, service details, definition list, helper note, form actions, primary/secondary buttons, and error message. Preserve mobile order with the form before the summary and actions after the summary.

- [ ] **Step 6: Run source assertions**

Run:

```sh
node -e "const fs=require('fs'); const jsx=fs.readFileSync('frontend-mua/src/App.jsx','utf8'); for (const value of ['grid','summary-panel','detail-group','focus:']) if (!jsx.includes(value)) throw new Error('Missing migrated booking utility or hook: '+value); console.log('PASS: booking utilities present')"
```

Expected: PASS.

### Task 4: Migrate success page and remaining responsive behavior

**Files:**
- Modify: `frontend-mua/src/App.jsx:207-222`
- Modify: `frontend-mua/src/App.css:118-137, mobile and short-viewport rules`

- [ ] **Step 1: Convert success page structure**

Use utilities for centering, surface, max width, responsive padding, success mark, heading, body copy, next-step panel, ordered list, and reset button. Preserve success status content and `resetBooking` behavior.

- [ ] **Step 2: Convert responsive and reduced-motion utilities**

Move component-specific mobile and short-viewport rules into responsive Tailwind variants. Keep global reduced-motion handling in `index.css`; do not add motion behavior.

- [ ] **Step 3: Run lint and build**

Expected: lint and build pass with no CSS optimizer warnings.

### Task 5: Remove migrated CSS and validate the hybrid boundary

**Files:**
- Modify: `frontend-mua/src/App.css`
- Modify: `frontend-mua/src/index.css`

- [ ] **Step 1: Remove obsolete component selectors**

Delete selectors whose layout, typography, spacing, responsive, hover, focus, selected, or error behavior has moved to JSX utilities. Retain only font/global concerns, artwork pseudo-elements and geometry, spinner keyframes, and selectors with a documented need for CSS.

- [ ] **Step 2: Check selector inventory**

Run:

```sh
grep -nE '^\.(site-header|hero-section|booking-shell|booking-grid|form-panel|summary-panel|form-actions|success-page|site-footer|field|button|progress|service-option|review-row)' frontend-mua/src/App.css
```

Expected: no migrated layout selectors remain; any remaining match must be a deliberate custom-art or animation hook.

- [ ] **Step 3: Run final validation**

Run:

```sh
bun run --cwd frontend-mua lint
bun run --cwd frontend-mua build
git diff --check
```

Expected: lint has 0 warnings and 0 errors, build succeeds without warnings, and diff check is clean.

- [ ] **Step 4: Check diagnostics**

Refresh diagnostics for `frontend-mua/src/App.jsx`, `frontend-mua/src/App.css`, and `frontend-mua/src/index.css`. Expected: no errors or warnings.

- [ ] **Step 5: Review the final diff**

Confirm no changes to routes, state, validators, API payloads, event names, text content, semantic labels, or ARIA attributes unless a class-only edit is required by the migration.
