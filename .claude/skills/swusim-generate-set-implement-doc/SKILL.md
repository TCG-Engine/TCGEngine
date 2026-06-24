---
name: swusim-generate-set-implement-doc
description: Use when starting a NEW SWU set to generate its consolidated SWUSim/docs/<set>-implement.md plan (for swusim-implement-set-plan to drive). Scans the generated card dictionaries, classifies every card, surveys for unbuilt core mechanics (hard-stops if found), then batches needs-work cards autonomy-first. Generate-only; one set abbreviation as input.
---

# SWUSim — Generate Set Implement Doc

Generate the single runnable `SWUSim/docs/<set>-implement.md` plan for one SWU set.
Spec: `docs/superpowers/specs/2026-06-18-swusim-generate-set-implement-doc-design.md`.

This skill writes **no card logic**. It produces the plan that `swusim-implement-set-plan`
then drives card-by-card via `swusim-implement-card`.

## Invocation

Argument: one set abbreviation. Allowed: any SWU set — Premier (`JTL`, `LOF`, `SEC`, `IBH`,
`LAW`, `ASH`) or Eternal-only (`SOR`, `SHD`, `TWI`, `TS26`). The earlier Premier-only deferral
of SHD/TWI/TS26 is lifted (2026-06-25 — see memory `project-set-scope-formats`).

**Guards (refuse and stop):**
- `SOR` — already complete (skip unless re-surveying intentionally).
- Anything not a recognized set abbreviation — confirm with the user.

First, invoke **`swusim-session-start`** to load project memory + `.claude/SWUSim/instructions.md`
(zone schema, conventions, file status), then proceed.

## Flow (overview)

```
Stage 1  scan + classify inventory  +  Stage 0.5 core-mechanic survey
         -> write scratch .claude/tmp/<set>-inventory.md  -> STOP for human review
Gate     unbuilt core mechanics?  YES -> report blockers, STOP (no plan).
                                  NO  -> continue
Stage 2  batch (autonomy-first) + autonomy-tag -> write SWUSim/docs/<set>-implement.md
```

## Stage 1 — Scan & Classify

Set `SET` to the (validated) abbreviation, `DICT=SWUSim/GeneratedCode/GeneratedCardDictionaries.php`,
`STUBS=SWUSim/GeneratedCode/GeneratedAbilityStubs.php`. All scans are read-only.

**1. List the set's CardIDs:**
```bash
awk '/\$setData = array \(/{f=1;next} f&&/^  \$[a-zA-Z]+Data = array/{exit} f{print}' $DICT \
  | grep "=> '$SET'" | grep -oE "^  '[A-Z0-9_]+'" | tr -d " '" | sort
```

**2. Get each card's type** (slice the `$typeData` block, single-line values):
```bash
awk '/\$typeData = array \(/{f=1;next} f&&/^  \$[a-zA-Z]+Data = array/{exit} f{print}' $DICT \
  | grep -E "^  '${SET}_"
```
Repeat the same block-slice pattern for `$titleData` (names) and `$textData` (ability text;
**multi-line** — slice the whole block, then read per-ID with
`sed -n "/^  '${SET}_NNN' =>/,/^  '${SET}_/p"`). Use `$deployTextData` for a Leader's deployed side.

**3. Trigger-stub presence** — a card has a triggered-ability stub iff its ID appears as a `case`:
```bash
grep -c "case '${SET}_NNN':" $STUBS  # >=1 means it has a trigger stub
```

**Classification buckets** (assign each CardID exactly one):

| Bucket | Rule | Disposition |
|---|---|---|
| **VANILLA** | blank `$textData`, type Unit/Token Unit/Upgrade/Token Upgrade | auto-wired no-op |
| **KEYWORD-ONLY** | non-blank text that is ONLY registered keyword(s) + reminder text, **and** no trigger stub | auto-wired no-op |
| **BASE** | type Base — no-op if blank/standard, NEEDS-WORK if it has an ability | per text |
| **LEADER** | type Leader — always NEEDS-WORK (leader + deployed sides) | Stage 2 |
| **NEEDS-WORK** | has a trigger stub, OR non-keyword ability text (passive / activated / triggered) | Stage 2 |

**Judgment note:** a trigger stub means NEEDS-WORK, but **absence of a stub does NOT mean
no-op** — passive ("while you control X, +1/+0") and activated ("Action [Exhaust]: …")
abilities produce no stub yet still need a handler. So for every no-stub card with non-blank
text, READ the text and decide KEYWORD-ONLY vs NEEDS-WORK. The human review (Stage 1 gate)
backstops this boundary.

> **Blank-text signal:** `$textData` is the ONE array the generator does NOT null-filter — it
> keeps blank cards as `'<ID>' => '',`. So blank text = the value is the empty string, detected
> directly:
> ```bash
> awk '/\$textData = array \(/{f=1;next} f&&/^  \$[a-zA-Z]+Data = array/{exit} f{print}' $DICT \
>   | grep -E "^  '${SET}_[0-9T]+' => '',?$"
> ```
> A blank-text Unit/Upgrade → VANILLA; a blank-text Base/Token → no-op of its own kind (still
> auto-wired). Do NOT treat "absent from the block" as blank — every card has a `$textData` row.

## Stage 0.5 — Core-Mechanic Survey (run during Stage 1)

Detect set-defining mechanics the ENGINE does not yet implement and that would block large
parts of the plan (e.g. Piloting, Plot, Credits).

1. **Gather candidate mechanic terms** from the set: keyword tokens in the set's `$textData`,
   plus distinctive mechanic nouns/verbs (Pilot/Piloting, Plot, Credit/Credits, and any
   capitalized/bolded keyword you don't recognize from the implemented set).
2. **Engine-reference check (evidence, NOT verdict):**
   ```bash
   grep -rilE '<term>' SWUSim/Custom/ SWUSim/GeneratedCode/GeneratedKeywordCode.php Data/ProcessKeywordsSWU.php
   ```
   A hit can be a real implementation OR merely a parsed data field (e.g. `$pilotingCostData`
   in the dictionary) or a comment. So **judge**: does the engine actually IMPLEMENT the
   mechanic, or only parse its dictionary field? When unsure, treat it as a candidate and let
   the human decide at the gate.
3. **Confirm via the CR:** look the term up in `.claude/SWUSim/refs/comprehensive-rules.md`
   (and `.claude/SWUSim/refs/game-refs.md`). Only a term the CR defines as a mechanic is a
   real blocker.
4. **Count dependents:** how many of the set's cards reference the mechanic in their text:
   ```bash
   awk '/\$textData = array \(/{f=1;next} f&&/^  \$[a-zA-Z]+Data = array/{exit} f{print}' $DICT \
     | grep -E "^  '${SET}_" | grep -ciE '<term>'
   ```

Report each confirmed-unbuilt mechanic as: **name + CR section ref + dependent-card count**.

## Gate (after the Stage 1 review)

- **Unbuilt core mechanic(s) confirmed → HARD STOP.** Report the blockers; write **no** plan.
  Tell the user to build those foundations first (a separate `swusim-implement-card` / agent
  effort), then re-invoke this skill. The re-run re-surveys; once the mechanic is wired into
  `SWUSim/Custom/*.php` (or the keyword code), the engine-reference check finds it and the gate
  clears — no hand-maintained "implemented mechanics" list to update.
- **None → continue to Stage 2.**

## Stage 1 Output — Scratch Inventory & Review

Write `.claude/tmp/<set>-inventory.md` (scratch; deletable after Stage 2):

```markdown
# <SET> — Stage 1 Inventory (scratch)

## ⚠ New core mechanics introduced by this set — build first
<one line per confirmed-unbuilt mechanic: name — CR §x.y — N dependent cards>
<or: "None detected.">

## Card inventory (N cards)
| ID | Name | Type | Bucket | Note (needs-work text, one line) |
|----|------|------|--------|----------------------------------|
| <SET>_001 | <name> | Leader | NEEDS-WORK | leader side + deployed OnAttack … |
| <SET>_046 | <name> | Unit   | VANILLA | — |
| <SET>_047 | <name> | Unit   | KEYWORD-ONLY | Raid 2 |
...
```

Every `<SET>_NNN` appears in exactly one row. Include the one-line text for NEEDS-WORK rows so
the human can sanity-check the KEYWORD-ONLY vs NEEDS-WORK boundary.

**Then STOP and ask the user to review** the scratch inventory — both the core-mechanic section
(drives the gate) and the bucket assignments. Do not proceed to Stage 2 until they confirm.

## Stage 2 — Batch & Tag (runs only when the gate is clear)

Operate on the NEEDS-WORK cards.

**Autonomy tag** (per card, lifted to the batch):
- `pair-programmed` if it needs a new decision-type/UI, a new shared subsystem, or an
  ambiguous ruling the dictionary/CR doesn't settle.
- `autonomous` otherwise.
- The tag is a STARTING ESTIMATE — `swusim-implement-set-plan` still escalates on emergent
  forks during the run (see memory `feedback-implement-card-gate`).

**Grouping (autonomy-first):**
- All **autonomous** phases first, then all **pair-programmed** phases (longest unattended run
  before the first human checkpoint).
- Each phase is a **shared-mechanic group**; order phases so a foundational mechanic precedes
  the cards that depend on it.
- Batches ~2–5 cards.

## Output Doc Format — `SWUSim/docs/<set>-implement.md` (the only persistent artifact)

```markdown
# <SET> — Card Implementation Plan

<N> cards total: <breakdown by type>. <M> needs-work, <K> auto-wired (vanilla/keyword-only/base).

### Already Done
<comma-separated VANILLA + KEYWORD-ONLY + no-op BASE ids — the auto-wired "done by
 classification" cards. swusim-implement-set-plan appends implemented ids to this line.>

## Phase 1 — <mechanic> (autonomous)
- [ ] **Batch 1.1 — <SET>_012, <SET>_045**
  - <SET>_012 <name>: OnAttack deal 2 to a unit
  - <SET>_045 <name>: WhenPlayed deal 1 to base
- [ ] **Batch 1.2 — …**

## Phase N — <new subsystem> (pair-programmed)
- [ ] **Batch N.1 — <SET>_101 <name>** …
```

Contract elements the loop reads/updates: `## Phase X` headers, `- [ ] **Batch X.Y …**` lines
carrying CardIDs, and the `### Already Done` line. Per-card one-liners are for at-a-glance
reading only; `swusim-implement-card` looks up full card text itself at run time.

After writing the doc, tell the user: the plan is at `SWUSim/docs/<set>-implement.md`, the
scratch inventory can be deleted, and `swusim-implement-set-plan` can now drive it.
**Never commit** — the user commits manually.
