---
name: hellbreaksim-implement-set-plan
description: Use when the user wants to drive a whole multi-batch Hellbreak implementation plan doc (HellbreakSim/docs/<set>-implement.md) to completion in one session — "run the DOT plan", "work through the remaining phases". For many batches across phases; for a single batch use hellbreaksim-implement-card. Pass --iterative to implement ONE CARD PER PASS and stop for review after each.
---

# HellbreakSim — Implement Set Plan

Thin orchestrator: drive a plan doc to completion by looping `hellbreaksim-implement-card` per
batch, keeping the plan doc current, and running a retro at two checkpoints. The plan doc is the
source of truth for *what* and in *what order*; this skill is the loop that runs it. It writes no
card logic itself.

Two facts shape the whole run, and both differ from the SWUSim equivalent:

- **Abilities are database rows, not files.** Nothing this run produces shows up in `git diff`, so
  the plan doc is the only durable record of progress until the user exports the SQL.
- **Every card needs its own fixture.** There is no scenario-template DSL here — the loop unit is
  bounded by fixture-building time, so batches are small.

## Modes

**Default:** the loop unit is a **batch**, the user gives one "go", and the run proceeds unattended.

**`--iterative`:** the loop unit is a **single card**, and the run **stops after every card** for
review. Use it when the user says "one at a time", or on anything where each card deserves a
dedicated look. Where the two disagree, the `--iterative` column wins:

| | default | `--iterative` |
|---|---|---|
| loop unit | batch | one card (a Monster = one card, **both faces**, one pass) |
| approval | one "go" for the range | stop and hand off after every card |
| hard / ambiguous card | defer to backlog, keep running | raise it at that card's own review |
| retro | two checkpoints | end of run, or when the user calls it |
| green gate | per batch | per card |

## Step 1 — Orient

1. Invoke **`hellbreaksim-session-start`**.
2. Read the plan doc end to end. Identify `## Phase X` headers and `- [ ] **Batch X.Y …**` lines
   with their CardIDs. Scope to the phase range the user named, else start at the first unchecked
   batch.
3. **Regenerate before reading progress.** `GeneratedMacroCode.php` is built from local DB rows and
   gitignored, so a stale copy misreports what is done:
   ```bash
   docker exec -w /var/www/html/TCGEngine otmtcge-hellbreaksim-web-server-1 \
     php zzGameCodeGenerator.php rootName=HellbreakSim
   ```
4. **Capture a baseline.** Run the fixture suite and every Hellbreak unit suite and record the
   counts. A test that was already red is not yours, and you need to be able to say so later.

## Step 2 — State the contract, wait for one "go"

> **`--iterative` skips most of this.** State instead: the ordered card list, that each card gets
> the full fixture + negative bar, that you stop after each for review, and that you never commit.
> Then start the first card.

Lay it out so the user confirms or amends once, then run the range unattended:

> For this run I'll:
> - work through the in-scope batches without pausing for per-batch review;
> - hold every card to the `hellbreaksim-implement-card` bar — a fixture that goes RED before the
>   ability exists, then the positive, the **negative that proves each gate is load-bearing**, the
>   take/decline branch, the no-legal-target case, and for a Monster **both faces**;
> - green-gate each batch on the fixture suite **and** all six Hellbreak unit suites before moving on;
> - **defer rather than halt** — a card needing a new dispatch point, a new shared helper, or a
>   ruling the rulebook does not settle goes to a backlog and I keep running the rest; I surface the
>   backlog at the next retro checkpoint;
> - never commit, and ask before changing any existing passing test.
>
> Say **go** and I'll run it.

If the user amends a rule, honor the amendment for the whole run.

## Step 3 — Per-batch loop

> **`--iterative`:** substitute *card* for *batch*, and after each card update the plan doc and
> **STOP**. Do not start the next one, and do not "get a head start" — the review may change how the
> next card is built.

For each in-scope batch, in plan order:

1. **Invoke `hellbreaksim-implement-card`** with the batch's CardIDs. It owns the real work.
2. **Verify green** — fixture suite `0 failed`, unit suites clean, and the batch's new assertions
   actually run. A red batch is not done.
3. **Update the plan doc** — flip `- [ ]` → `- [x]`, append a one-line done-note (what fired, which
   helper was added), and append the CardIDs to `### Already Done`.
4. **Maintain a todo list** — one item per batch plus the two retro checkpoints.

### The per-card review hand-off (`--iterative`)

Short enough to read in one pass. Every review states:

- **The card** — ID, name, and its printed text (both faces for a Monster), so the user can judge
  the implementation without looking it up.
- **What you built** — the macro(s) chosen, `macro` vs `listener` and why, any shared helper touched.
- **The judgement calls** — every place the text was ambiguous and you picked a reading. This is the
  part the user is actually reviewing. If there were none, say so.
- **Coverage** — the fixture sections written, calling out the negative explicitly.
- **Suites** — before → after, `0 failed`.
- **Anything unsettled** — a rules question, a design fork, a deferred clause.

### Resumability

An `--iterative` run will cross a session boundary, and **the ability rows are invisible to git**,
so "where am I" must live on disk. The plan doc's checkboxes and `### Already Done` line ARE the
resume point — update them *before* handing off, never after the user replies. On a cold start,
regenerate and recompute the remaining list from the macro keys rather than trusting a checkbox.

## Step 4 — Retro (two checkpoints only)

> **`--iterative`:** retro at the END of the run, or when the user asks. Not after every card —
> the review hand-off already surfaces what they need.

Run it at **exactly two** points: the **autonomous → pair-programmed handoff**, and the **end of
the run**. At each:

1. What did the phases since the last checkpoint teach that would improve
   `hellbreaksim-implement-card`? Prefer extending an existing row or gotcha over adding a new one.
2. Fold the high-value lessons in yourself.
3. **Surface the deferral backlog** — each parked card and its one-line reason, so the user can
   decide. Re-test each first: infrastructure built by a later batch often unblocks it.
4. Note what you folded in. Change nothing else.

## Step 5 — Defer vs halt

**DEFER and keep running** (the common case): a card whose ruling the rulebook does not settle; a
self-contained design choice affecting only that card; a hard card you would otherwise grind on.

**HALT and ask** only when continuing is genuinely blocked:
- a card needs a **new macro / dispatch point** in `GameSchema.txt` — that is a schema change and a
  real design decision, and other in-scope cards probably need it too;
- a batch needs **new shared infrastructure** the rest of the scope depends on;
- you are stuck too long on one card;
- you would otherwise change an existing passing test.

Everything else is yours: a wrong assertion, a fixture whose loyalty blocks the card, a misdiagnosed
harness failure. Fix and continue.

## Step 6 — Finish

Report: **start → end suite counts**, the batches done, the retros folded, and the **remaining
backlog** with reasons. A set is **not** complete while the backlog is non-empty — say so plainly.

Then the two things that are easy to forget and expensive to miss:

- **⚠ The ability SQL export.** Everything this run produced lives only in the local `card_abilities`
  table. `zzCodeGeneratorMain.php` → **Card ability SQL → Export SQL**, then Import SQL on prod.
- **The tree is uncommitted** — the user commits manually.

If they are wrapping up, invoke **`hellbreaksim-session-close`**.

## Common mistakes

| Mistake | Fix |
|---|---|
| Skipping the "go" gate | State the contract first; one confirmation, then full speed. |
| Reading progress off a stale `GeneratedMacroCode.php` | Regenerate first — it is built from DB rows and gitignored. |
| Assuming `git diff` shows the run's work | It shows none of it. The plan doc is the record until the SQL is exported. |
| Halting on every hard card | Defer to the backlog and keep running. Halt only for a schema change or a shared blocker. |
| Marking a batch done while a suite is red | Green-gate on the fixture suite **and** all six unit suites. |
| Marking a Monster done on its lurking face | Two faces, two ability sets, each proven. |
| Running ahead in `--iterative` mode | The point of the mode is that a review can change the next card. Stop means stop. |
| Dropping the bar because `--iterative` feels lighter | It changes review granularity, not scope. Same fixture, same negative, per card. |
| Spawning subagents to go faster | The loop is sequential by design — each batch's green suite gates the next. |
| Committing at the end | Never. The user commits manually. |
