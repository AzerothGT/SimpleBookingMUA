# Booking Date and Time Step Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Place the requested end time beside the date selection in step 2 while preserving the four-step booking flow and API contract.

**Architecture:** Keep `form.date` and `form.endTime` as the existing source of truth. Move the end-time field markup into step 2, validate both values in `validateStepTwo`, and remove only the duplicate step-3 field. Keep schedule checking and submit payload unchanged.

**Tech Stack:** React 19, existing JSX form validation, Tailwind-powered component CSS.

---

### Task 1: Move date and time into one step

**Files:**
- Modify: `frontend-mua/src/App.jsx:92-96,274-304`
- Test: inline source assertions from the project root

- [ ] **Step 1: Write and run the failing source assertion**

Run:

```sh
node -e "const fs=require('fs'); const jsx=fs.readFileSync('frontend-mua/src/App.jsx','utf8'); if (!jsx.includes('Pilih tanggal & jam')) throw new Error('RED: combined step label missing'); if (!jsx.includes('step === 2') || !jsx.includes('id=\"end-time\"')) throw new Error('RED: date/time step structure missing'); console.log('PASS')"
```

Expected: FAIL because step 2 is still labeled `Pilih tanggal` and the end-time field is currently rendered under step 3.

- [ ] **Step 2: Update the progress label**

Change the step label array entry from `Pilih tanggal` to `Pilih tanggal & jam`.

- [ ] **Step 3: Validate end time in step 2**

Update `validateStepTwo` so it returns `endTime: 'Masukkan jam selesai.'` when `form.endTime` is empty, alongside the existing date validation.

- [ ] **Step 4: Move the end-time field into step 2**

Render the existing `end-time` label/input directly after the date row or schedule status in the `step === 2` fragment. Preserve its value, `onChange`, `aria-invalid`, `aria-describedby`, and error message.

- [ ] **Step 5: Remove the duplicate field from step 3**

Delete only the `Jam selesai yang diusulkan` field and its surrounding `Waktu` fieldset from step 3. Keep `Kontak`, `Lokasi`, and `Catatan tambahan` unchanged.

- [ ] **Step 6: Run the GREEN source assertion**

Run:

```sh
node -e "const fs=require('fs'); const jsx=fs.readFileSync('frontend-mua/src/App.jsx','utf8'); if (!jsx.includes('Pilih tanggal & jam')) throw new Error('Missing combined step label'); const stepTwo=jsx.slice(jsx.indexOf('{step === 2'), jsx.indexOf('{step === 3')); if (!stepTwo.includes('id=\"booking-date\"') || !stepTwo.includes('id=\"end-time\"')) throw new Error('Date and time are not both in step 2'); const stepThree=jsx.slice(jsx.indexOf('{step === 3'), jsx.indexOf('{step === 4')); if (stepThree.includes('id=\"end-time\"')) throw new Error('Duplicate end-time field remains in step 3'); console.log('PASS: combined date/time step')"
```

Expected: PASS.

### Task 2: Validate the booking flow

**Files:**
- Validate: `frontend-mua/src/App.jsx`
- Validate: `frontend-mua/src/App.css`

- [ ] **Step 1: Run lint**

```sh
bun run --cwd frontend-mua lint
```

Expected: 0 warnings and 0 errors.

- [ ] **Step 2: Run production build**

```sh
bun run --cwd frontend-mua build
```

Expected: Vite build succeeds without errors.

- [ ] **Step 3: Refresh diagnostics**

Check diagnostics for `frontend-mua/src/App.jsx` and `frontend-mua/src/App.css`. Expected: no code errors; Tailwind directive warnings in `App.css`, if shown by the editor language server, are known tooling false positives.
