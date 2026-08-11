# Booking Date and Time Step Design

## Goal

Keep date and proposed end time in the same booking step so users can check and review scheduling information together.

## Behavior

- Preserve the existing four-step flow.
- Rename step 2 from `Pilih tanggal` to `Pilih tanggal & jam`.
- Render the date input and `Jam selesai yang diusulkan` in step 2.
- Validate both `form.date` and `form.endTime` before advancing from step 2.
- Remove the end-time field from step 3 to avoid duplicate inputs.
- Keep the existing summary and API payload fields unchanged.
- Preserve schedule checking, error messages, and responsive layout.

## Files

- Modify `frontend-mua/src/App.jsx` for step labels, validation placement, and field markup.
- Modify `frontend-mua/src/App.css` only if the combined fields need spacing adjustments.

## Validation

Use a source assertion for the new label, colocated date/time fields, and absence of the step-3 duplicate. Run lint, build, and diagnostics for modified files.
