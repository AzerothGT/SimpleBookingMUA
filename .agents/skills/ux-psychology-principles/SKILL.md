---
name: ux-psychology-principles
description: Use when designing or reviewing UI/UX flows involving onboarding, forms, signup walls, pricing, upgrades, progress indicators, CTAs, conversion, or retention, especially when users feel overwhelmed, unmotivated, pressured, or uncertain.
---

# UX Psychology Principles

## Overview

Use psychology to reduce unnecessary friction and help users make confident decisions. Apply these principles transparently: improve clarity, momentum, and perceived value without deception, coercion, or removing user control.

## Application Workflow

1. Identify the user's goal and the exact point of friction.
2. Choose only the principle(s) that explain that friction; do not force all six into one flow.
3. Recommend a concrete change to the UI, content, sequence, or default state.
4. Explain the expected behavior change and its trade-off.
5. Check that the experience remains clear, accessible, reversible, and voluntary.
6. Define a measurable validation signal when useful: completion rate, time to complete, error rate, abandonment, activation, or cancellation rate.

## Quick Reference

| Principle | User tendency | Apply it by | Avoid |
|---|---|---|---|
| Smart defaults | Empty choices feel costly | Pre-fill common, safe values and make them easy to change | Hidden or self-serving defaults |
| Goal gradient | Motivation rises near a visible finish | Show truthful progress and a clear next step | Fake progress that misleads users |
| Reciprocity | People value an exchange more after receiving value | Demonstrate useful output before asking for signup or payment | Holding essential results hostage |
| IKEA/endowment effect | Participation creates ownership | Let users configure or build something meaningful before commitment | Manufacturing sunk-cost pressure |
| Loss aversion/status quo bias | Losses feel stronger than equivalent gains | Explain real consequences of inaction alongside benefits | Threats, fearmongering, or false urgency |
| Contrast effect | Value is judged relative to nearby anchors | Present price and benefits in useful context | Unfair anchors or hiding the total cost |

## The Six Principles

### 1. Smart Defaults — reduce decision fatigue

Do not start users with a blank form when a common, low-risk answer is known. Pre-fill or preselect the most likely option so the task becomes “scan and adjust” rather than “invent every answer.”

- Use realistic defaults based on context, such as the user's location, last selection, or most common booking duration.
- Label defaults clearly and keep every value editable.
- Prefer safe, reversible defaults for consent, notifications, payments, and privacy.
- Group advanced choices behind progressive disclosure instead of presenting every option at once.

### 2. Goal Gradient Effect — create honest momentum

People tend to act faster when the finish line feels close. Show progress, remaining effort, and the next action so users can see momentum.

- Use a truthful progress indicator with meaningful milestones.
- If setup is already partly complete because an account or imported data exists, count that completed work explicitly.
- Explain what remains and provide “save and continue later” where appropriate.
- Never claim a percentage that does not correspond to real completed work.

### 3. Reciprocity — provide value before commitment

A signup wall before any evidence of value can feel like the product is withholding the result. Let users experience a useful preview first when the product allows it.

- Show a sample result, estimate, preview, recommendation, or partial report before signup.
- Make the boundary clear: state what the preview contains and what signup unlocks.
- Ask for the minimum information needed at the moment it becomes useful.
- Keep the free value genuinely useful; do not make it a disguised obstruction.

### 4. IKEA and Endowment Effects — build ownership

Users value outcomes more when they helped shape them or can already imagine them as theirs. Allow meaningful configuration before asking for a high-commitment action.

- Let users select preferences, assemble a plan, customize a profile, or create a draft.
- Persist their work so a signup step does not erase their investment.
- Reflect their choices back in a preview or summary to reinforce ownership.
- Make the draft exportable or discardable; ownership must not become a trap.

### 5. Loss Aversion and Status Quo Bias — explain consequences honestly

People often weigh a possible loss more heavily than an equivalent gain. When asking users to upgrade, renew, or complete an action, explain what they will lose or risk by not acting—but only when the consequence is real.

- Pair benefits with concrete consequences: “Keep X” and “Without this, Y expires.”
- State dates, limits, eligibility, and cancellation rules plainly.
- Use neutral language and give users a reasonable alternative, such as staying on the free plan.
- Do not invent scarcity, imply irreversible damage, or shame users for declining.

### 6. Contrast Effect — provide a fair reference point

Users evaluate prices and features relative to nearby information rather than in isolation. The first relevant number or option can become an anchor.

- Show the relationship between price and the value, scope, or item it supports.
- Compare plans using the same units and clearly identify the recommended option.
- Display the total cost, recurring cadence, taxes, and important conditions before confirmation.
- Use anchors that are relevant and honest, not inflated list prices or unrelated expensive options.

## Example: Makeup Booking Flow

For a makeup booking form with service, date, duration, location, and add-ons:

1. Preselect the most common service and duration, while keeping both editable.
2. Ask for the date and location in a short sequence with a visible, truthful step count.
3. Show an estimated price and a preview of the booking summary before requiring account creation.
4. Let users adjust the look and add-ons, then preserve the draft when they sign up.
5. If a chosen date or quote expires, state the exact expiry and what changes—not a vague threat.
6. Present add-on prices in relation to the base service and show the complete total before payment.

## Ethical Guardrails

Before shipping a persuasion-oriented change, verify:

- The default is useful to the user, not merely the business.
- Progress and completion claims are accurate.
- The preview delivers real value and the signup boundary is explicit.
- User-created work is preserved and can be changed or removed.
- Loss framing describes a real, material consequence without fearmongering.
- Pricing, renewal, fees, and comparison anchors are visible and fair.
- Declining, skipping, undoing, and cancelling are reasonably easy.
- The experience works with keyboard navigation, assistive technology, and readable contrast.

## Common Mistakes

- Applying every principle at once instead of diagnosing one friction point.
- Treating a default as permission to hide important choices.
- Showing decorative progress that does not reflect actual work.
- Making the free preview too incomplete to be useful.
- Using sunk-cost pressure after users have invested time.
- Turning loss aversion into fabricated scarcity or guilt.
- Using a high anchor while hiding the final price or billing terms.
- Optimizing only signup clicks while ignoring activation, satisfaction, refunds, or cancellations.
