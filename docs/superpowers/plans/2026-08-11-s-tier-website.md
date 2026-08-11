# S-Tier Website Skill Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a project-local `s-tier-website` skill that guides agents toward minimal, scannable, responsive, and visually hierarchical web interfaces.

**Architecture:** A self-contained `SKILL.md` under `.agents/skills/s-tier-website/` with YAML frontmatter, a practical workflow, design-token guidance, one compact example, and a review checklist. No runtime code or supporting dependency is needed.

**Tech Stack:** Markdown, YAML frontmatter, Zed project-local agent skills.

---

### Task 1: Create the skill document

**Files:**
- Create: `SimpleBookingMUA/.agents/skills/s-tier-website/SKILL.md`

- [ ] **Step 1: Create the skill directory**

Run the project file tool to create `SimpleBookingMUA/.agents/skills/s-tier-website/`.

- [ ] **Step 2: Write YAML frontmatter**

Use the exact skill name `s-tier-website` and a description beginning with `Use when...` that mentions creating, polishing, or reviewing web UI, minimalism, scanability, responsive behavior, spacing, and visual hierarchy.

- [ ] **Step 3: Add the minimal design workflow**

Document the sequence: identify the primary outcome and action; design the core area first; group by similarity and proximity; start with generous spacing; establish `rem`/4-based tokens; create hierarchy; de-emphasize secondary information; audit responsive scanability and visual noise.

- [ ] **Step 4: Add practical rules and one example**

Include the dark/light neutral plus maximum two accent colors rule, line-height guidance for small text, consistency rules, justified exceptions, and one concise crowded-versus-focused UI example.

- [ ] **Step 5: Add audit checklist and common mistakes**

The checklist must cover primary action, grouping, spacing, `rem`, color, line-height, hierarchy, responsiveness, and unnecessary elements. Common mistakes must explain why deleting everything is not the same as minimal design.

### Task 2: Validate the skill

**Files:**
- Test: `SimpleBookingMUA/.agents/skills/s-tier-website/SKILL.md`

- [ ] **Step 1: Verify frontmatter and naming constraints**

Run a text inspection command against the skill file and confirm the directory name and frontmatter name are exactly `s-tier-website`, with only lowercase letters and single hyphens.

- [ ] **Step 2: Verify required guidance is discoverable**

Search the file for `primary`, `similarity`, `proximity`, `spacing`, `rem`, `line-height`, `hierarchy`, `responsive`, `accent`, and `checklist`. Each concept must appear in actionable instructions.

- [ ] **Step 3: Run pressure scenarios without and with the skill**

Use three scenarios: a crowded landing page with competing CTAs, a poorly grouped form, and a responsive component with pixel-only typography and cramped small text. Record the baseline tendencies before activation, then confirm the skill causes the agent to prioritize the primary goal, use consistent tokens, and state justified exceptions.

- [ ] **Step 4: Perform a final content audit**

Check for placeholders, vague directives, duplicate rules, unsupported framework assumptions, and any instruction that encourages removing useful content merely to make a page look sparse.
