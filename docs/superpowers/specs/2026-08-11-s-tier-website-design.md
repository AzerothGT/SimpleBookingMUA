# `s-tier-website` Skill Design

## Goal

Create a project-local Zed agent skill that guides agents when creating,
polishing, or reviewing web UI. The skill turns the supplied design principles
into a practical playbook for minimal, scannable, responsive, production-grade
interfaces.

## Scope

The skill applies to websites, landing pages, dashboards, forms, and reusable
web components. It is not a replacement for product strategy, accessibility
standards, or a framework-specific implementation guide.

The skill will live at:

`.agents/skills/s-tier-website/SKILL.md`

## Core Workflow

1. Identify the page's primary user outcome and primary action.
2. Design the core content/action area before secondary chrome or decoration.
3. Group related elements using similarity and proximity.
4. Start with generous spacing, then reduce only where relationships require it.
5. Establish a small design-token system using `rem` and a 4-based scale.
6. Create visual hierarchy through size, weight, contrast, and whitespace.
7. De-emphasize secondary information instead of competing with the primary goal.
8. Review the result for scanability, responsive behavior, and unnecessary visual noise.

## Rules to Encode

- Prefer the simplest layout that communicates the value clearly.
- Limit color to dark/light neutrals plus no more than two personality/accent colors.
- Use consistent spacing, radii, typography, and component treatments.
- Use `rem` for sizing and spacing where practical; avoid arbitrary pixel-only systems.
- Give smaller text more generous line-height.
- Make related elements look and feel related through shared visual properties.
- Avoid decorative headers, cards, badges, gradients, or copy that do not support the page goal.
- Allow exceptions only when a deliberate UX reason is stated.

## Expected Output When Activated

The agent should produce or explain:

- the primary page hierarchy and action;
- the token choices or mapping to an existing design system;
- the implementation or review findings;
- a concise final audit covering hierarchy, spacing, color, typography,
  responsiveness, and unnecessary elements.

## Example and Review Support

The skill will contain one compact before/after example showing how to reduce a
crowded page to its essential content and action. It will also include a quick
reference checklist, common mistakes, and a rationalization table to prevent
"minimal" from becoming vague or visually under-designed.

## Validation

Before finalizing the skill, test it against scenarios involving:

- a crowded landing page with too many competing CTAs;
- a form with weak grouping and inconsistent spacing;
- a responsive component with pixel-only typography and cramped small text.

Success means the skill directs the agent to prioritize the core outcome,
apply consistent tokens and hierarchy, and explain justified exceptions rather
than merely removing visual elements.
