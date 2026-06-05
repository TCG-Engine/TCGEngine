---
name: swusim-implement-set-plan
description: Use when the user wants to drive a whole multi-batch SWUSim implementation plan doc (e.g. docs/<set>-complex-plan.md) to completion in one session — "run/execute the <set> plan", "work through the remaining phases". For many card batches across phases at once; for a single batch use swusim-implement-card.
---

# SWUSim Implement Set-Plan

Thin orchestrator: drive a multi-batch implementation **plan doc** to completion by looping `swusim-implement-card` per batch, keeping the plan + set tracker current, and folding a retro into the card skill at two checkpoints — the autonomous→pair-programmed handoff and the end of the run. The plan doc (e.g. `docs/<set>-complex-plan.md`) is the source of truth for *what* to build and in *what order*; this skill is the loop that runs it. It writes no card logic itself — `swusim-implement-card` does that. Keep in mind the 90% confidence rule established in the `swusim-implement-card` skill itself. This is per card. Not per batch.

## Step 1 — Orient

1. Invoke **`swusim-session-start`** (loads project memory + `.claude/SWUSim/instructions.md` — zone schema, conventions, file status).
2. Read the target **plan doc** end to end. Identify its **phases** (`## Phase X`) and **batches** (`- [ ] **Batch X.Y …**` with card IDs). If the user named a phase range, scope to it; otherwise start at the first unchecked batch.
3. Capture a **baseline regression**: `curl http://localhost:3400/TCGEngine/zzRegressionSWUSim.php`. Record passing/failing — every later "+N" is measured against this, and a pre-existing red test is not yours.

## Step 2 — State the autonomy contract, then wait for one "go"

Lay the contract out so the user can confirm or amend it ONCE, then run the whole range unattended. **Do not start implementing until the user says "go".**

> For this run I'll:
> - proceed through the in-scope batches/phases without pausing for per-batch review;
> - run the retro (`references/swu-impl-retro.txt`) at **two checkpoints only** — when the **autonomous phases** are all done (the handoff into pair-programming) and again at the **end of the pair-programmed phases** — folding approved lessons into `swusim-implement-card` myself (no review);
> - **only stop to ask** on a genuine design fork — a Hard-tier card, an ambiguous ruling the dictionary/CR doesn't settle, or new shared infrastructure with a real design choice — or if I'm stuck too long on a single card;
> - never commit (you commit manually); never run host PHP (regression only via the curl endpoint);
> - ask before modifying any **existing confirmed** test.
>
> Say **go** and I'll run it.

If the user amends a rule, honor the amendment for the whole run.

## Step 3 — Per-batch loop

For each in-scope batch, in plan order:

1. **Invoke `swusim-implement-card`** with the batch's card IDs. It owns the real work: triage (vanilla / keyword-only = verify-only no-ops), look up text, write all DSL tests first (RED), implement, drive the regression to green. Honor its tier gate — no hard stop for Simple/Medium, STOP only for Hard.
2. **Verify green** before moving on: the regression shows `0 failed` and the batch's new tests pass. A red batch is not done — fix it or escalate (Step 5).
3. **Update the docs:**
   - Flip the batch checkbox `- [ ]` → `- [x]` in the plan and append a one-line done-note (passing count + the key infra/helper added).
   - Append the implemented card IDs to the set tracker `SWUSim/docs/{set}-implement.md`'s `### Already Done` line (`{set}` derived from the card ID — `SOR_146` → `sor`). Skip IDs already listed; don't reorder.
4. **Maintain a todo list** (one item per batch + the two retro checkpoints); mark items done as you go.

## Step 4 — Retro (two checkpoints only)

Run the retro at **exactly two** points — **not** after every phase:

- **Autonomous → pair-programmed handoff:** when the last `(autonomous)` phase's batches are all done, before starting the first `(pair-programmed)` phase.
- **End of run:** when the last `(pair-programmed)` phase completes.

At each checkpoint:

1. Run the **`references/swu-impl-retro.txt`** reflection: what did the phases since the last checkpoint teach that would improve `swusim-implement-card`?
2. **Fold the high-value lessons into `swusim-implement-card`** yourself (you have edit permission on it for set-plan runs). Prefer extending an existing implementation-table row / gotcha note over adding a new one; keep it concise.
3. Note what you folded in the running summary. Change nothing else.

## Step 5 — Escalation (the only reasons to stop)

Stop and ask the user ONLY when:
- a card is **Hard-tier**, or its ruling is ambiguous and the dictionary/CR doesn't settle it;
- a batch needs **new shared infrastructure with a real design choice** the user alone should make — flag that *specific* fork, don't gate the whole batch;
- you're **stuck too long on one card** — don't grind, ask for help;
- you'd otherwise **modify an existing confirmed test** — ask first.

Everything else is yours to handle: a wrong EXPECT, a fixture's aspect cost, a misdiagnosed "harness" failure — fix and continue.

## Step 6 — Finish

When the scope is complete, report **start → end regression counts**, the phases/batches done, and the retros folded. Remind the user the tree is **uncommitted** (they commit manually). If they're wrapping up the session, invoke **`swusim-session-close`** to update project memory.

## Common mistakes

| Mistake | Fix |
|---|---|
| Skipping the "go" gate and just running | State the contract first; one confirmation, then full speed. |
| Calling a red test a "harness limitation" | Suspect a **bad EXPECT** first — defeated non-token upgrades go to discard; a player's OWN defeated units go to THEIR discard; double-pip aspect cards cost +4 off-aspect. Verify before blaming the harness. |
| Running a retro after every phase | Only two retros: at the autonomous→pair-programmed handoff and at the very end. Don't retro per-phase. |
| Updating the plan but not the set tracker (or vice-versa) | Both: plan checkbox **and** `SWUSim/docs/{set}-implement.md`. |
| Spawning subagents to "go faster" | Run batches inline — the loop is sequential by design (each batch's green regression gates the next). |
| Committing at the end | Never. The user commits manually. |
| Marking a batch done while regression is red | Green-gate every batch; `0 failed` or it's not done. |
