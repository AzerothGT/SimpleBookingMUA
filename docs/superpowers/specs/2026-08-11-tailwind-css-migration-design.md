# Tailwind CSS Migration Design

## Goal

Convert the entire `frontend-mua/src/App.css` styling to Tailwind CSS while preserving the current booking experience, visual direction, responsive behavior, and accessibility.

## Scope

Migrate styling for:

- site header and footer;
- hero section and artwork;
- booking progress, form, summary, actions, and states;
- success page;
- responsive breakpoints and reduced-motion behavior.

The migration will not change booking data flow, validation, API calls, copy, routes, or component behavior.

## Approach

Use a hybrid Tailwind migration:

1. Move layout, spacing, sizing, typography, responsive rules, and interaction states into utility classes in `App.jsx`.
2. Keep a small CSS file for font imports, shared base styles, design tokens, artwork pseudo-elements, custom orbit/card shapes, and the spinner keyframe.
3. Use Tailwind arbitrary values only for intentional visual details that do not belong in the shared spacing/type scale.
4. Remove obsolete component selectors from `App.css` after the JSX migration.

## Files

- Modify `frontend-mua/src/App.jsx`: add Tailwind classes to the rendered UI.
- Modify `frontend-mua/src/App.css`: retain only justified custom CSS and tokens.
- Keep `frontend-mua/src/index.css`: Tailwind import, fonts, global reset, and reduced-motion base rules remain here.
- Do not add a Tailwind config unless the migration requires project-specific theme extensions.

## Preservation Requirements

- Keep the existing editorial green, paper, sage, lime, and orange visual direction.
- Preserve desktop/mobile layouts, including the mobile form-before-summary order.
- Preserve selected, hover, focus, error, loading, and success states.
- Preserve semantic HTML, labels, fieldsets, ARIA attributes, and keyboard usability.
- Preserve reduced-motion behavior.

## Validation

Run lint and production build. Search for removed component selectors and confirm only intentional custom selectors remain. Check diagnostics for modified files and inspect the diff for accidental behavior or content changes.
