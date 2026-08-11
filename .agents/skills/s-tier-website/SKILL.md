---
name: s-tier-website
description: Use when creating, polishing, or reviewing web UI that feels crowded, hard to scan, visually inconsistent, or weakly prioritized, especially when improving minimalism, responsive spacing, typography, color, or visual hierarchy.
---

# S-Tier Website

Build the simplest interface that makes the page's primary value and action obvious. Minimal does not mean empty: every visible element should support the user's goal, comprehension, trust, or completion.

## Workflow

1. State the page's primary user outcome and primary action in one sentence.
2. Design the core content/action area first. Add header, navigation, trust signals, and decoration only after the core works.
3. Group related elements with **similarity** (shared size, shape, color, or treatment) and **proximity** (closer spacing within a group, larger spacing between groups).
4. Start with generous spacing. Reduce it only when a tighter relationship is intentional and clear.
5. Establish tokens before styling details: use `rem` for type, spacing, and sizing where practical, with a consistent 4-based scale (`0.25rem`, `0.5rem`, `0.75rem`, `1rem`, `1.5rem`, `2rem`, etc.). Map to the existing design system when one exists.
6. Create hierarchy with size, font weight, contrast, and whitespace. Make the primary action unmistakable; de-emphasize secondary copy rather than making everything loud.
7. Review the page at narrow and wide viewports. Check scanability in a few seconds, readable line lengths, wrapping, touch targets, and whether hierarchy survives responsive changes.

## Visual Rules

- Prefer one clear primary CTA. Demote, combine, or remove competing actions.
- Keep the palette restrained: dark/light neutrals plus no more than two personality or accent colors.
- Keep spacing, radii, typography, controls, and interaction states consistent.
- Small text needs more line-height than large text; never solve density by squeezing leading.
- Use reduced contrast for secondary information, but preserve readable contrast and accessibility.
- Avoid decorative headers, cards, badges, gradients, copy, or separators that do not support the primary goal.
- Do not remove useful content merely to make the page sparse. Break long content into scannable groups instead.
- If breaking a rule improves usability, name the UX reason explicitly.

## Compact Example

**Crowded:** hero title, three equally prominent buttons, five badges, a decorative header, and unrelated feature cards above the fold.

**Focused:** one outcome-led title, one supporting sentence, one primary button, one quiet secondary link, and only the trust signal needed for the decision. Use generous group spacing, a restrained palette, and a clear heading/body contrast.

## Quick Audit

- [ ] Can the primary outcome and action be identified immediately?
- [ ] Is the core content designed before secondary chrome?
- [ ] Are related elements grouped by similarity and proximity?
- [ ] Does spacing use a consistent 4-based `rem` scale?
- [ ] Are there dark/light neutrals and at most two accents?
- [ ] Is small text given comfortable line-height?
- [ ] Do size, weight, contrast, and whitespace create one clear hierarchy?
- [ ] Does the hierarchy remain usable on narrow screens?
- [ ] Can any element or copy be removed without reducing clarity or trust?

## Common Failure Modes

| Mistake | Correction |
|---|---|
| Starting with the header or decoration | Start with the primary content and action |
| Giving every CTA equal emphasis | Choose one primary action and demote the rest |
| Tightening spacing immediately | Begin spacious, then tighten only within intentional groups |
| Arbitrary pixels everywhere | Use `rem` tokens and a consistent 4-based scale |
| More colors to create personality | Use two or fewer accents and let hierarchy do the work |
| Treating minimal as deletion | Preserve useful content; improve grouping and scanability |
| Making secondary text invisible | Reduce emphasis while maintaining readable contrast |
