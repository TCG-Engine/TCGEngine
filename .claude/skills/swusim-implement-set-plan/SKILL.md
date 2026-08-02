---
name: swusim-implement-set-plan
description: Use when the user wants to drive a whole multi-batch SWUSim implementation plan doc (e.g. docs/<set>-complex-plan.md) to completion in one session — "run/execute the <set> plan", "work through the remaining phases". For many card batches across phases at once; for a single batch use swusim-implement-card.
---

# SWUSim Implement Set-Plan

Thin orchestrator: drive a multi-batch implementation **plan doc** to completion by looping `swusim-implement-card` per batch, keeping the plan + set tracker current, and folding a retro into the card skill at two checkpoints — the autonomous→pair-programmed handoff and the end of the run. The plan doc (e.g. `docs/<set>-complex-plan.md`) is the source of truth for *what* to build and in *what order*; this skill is the loop that runs it. It writes no card logic itself — `swusim-implement-card` does that. Keep in mind the 98% confidence rule established in the `swusim-implement-card` skill itself. This is per card. Not per batch. **For leaders it is also per *side*:** the leader (front) side AND the leader unit (deployed `deployTextData`) side are separate ability sets that must *each* independently clear 98% — a leader with a finished front Action but an unimplemented deployed On Attack / When Deployed / passive / deployed Action is **not** Done (the ASH/LOF deployed-side gaps in `SWUSim/docs/leader-gaps.md` are exactly this miss). Don't mark a leader done on its front side alone.

## Step 1 — Orient

1. Invoke **`swusim-session-start`** (loads project memory — zone schema, conventions, file status — + the lean `.claude/SWUSim/instructions.md` orientation).
2. Read the target **plan doc** end to end. Identify its **phases** (`## Phase X`) and **batches** (`- [ ] **Batch X.Y …**` with card IDs). If the user named a phase range, scope to it; otherwise start at the first unchecked batch.
3. Capture a **baseline regression**: `curl http://localhost:3400/TCGEngine/zzRegressionSWUSim.php`. Record passing/failing — every later "+N" is measured against this, and a pre-existing red test is not yours.

## Step 2 — State the autonomy contract, then wait for one "go"

Lay the contract out so the user can confirm or amend it ONCE, then run the whole range unattended. **Do not start implementing until the user says "go".**

> For this run I'll:
> - proceed through the in-scope batches/phases without pausing for per-batch review;
> - hold every card to the **98% bar and the full coverage matrix** in `swusim-implement-card` — for each clause: positive, the NEGATIVE that proves the gate is load-bearing, take/decline, no-valid-target, quantity discrimination, boundary; and per card: the dispatch-path matrix (played / as an upgrade / as a token / put into play by another card / relocated / leader front vs deployed), value-CLASS variants, persistence across arena-move + control-change + request boundary, duration edges, interaction with shields/immunity/unpreventable/Credit payment, and scope exclusions. **Expect several times more sections per card than a happy-path pass would produce** — that is the point, and it is what stops a later validation pass from finding untested behavior;
> - run the retro (`references/swu-impl-retro.txt`) at **two checkpoints only** — when the **autonomous phases** are all done (the handoff into pair-programming) and again at the **end of the pair-programmed phases** — folding approved lessons into `swusim-implement-card` myself (no review);
> - **defer rather than halt:** when I hit a Hard-tier card, an ambiguous ruling the dictionary/CR can't settle, or a self-contained design fork, I **park it in a deferral backlog and keep running the rest of the scope fully autonomously** — I do NOT stop the whole run to ask. I surface the collected backlog at the next retro checkpoint for us to clear together. The ONLY things that halt the run mid-stream are a blocker the *rest of the scope genuinely depends on* (e.g. new shared infrastructure other in-scope cards need) or being stuck too long on one card;
> - never commit (you commit manually); never run host PHP (regression only via the curl endpoint);
> - ask before modifying any **existing confirmed** test.
>
> Say **go** and I'll run it.

**Why defer-don't-halt** (proven on the LAW run, user-confirmed preference): stopping the autonomous run on every Hard card fragments it into a dozen little stop-and-waits and kills momentum. Parking Hard/ambiguous cards in a backlog and finishing everything else first means the user gets one consolidated list of genuine design decisions to make together — and the deferred cards are often unblocked anyway by infrastructure a *later* batch builds, so several clear themselves by the time you reach the backlog.

If the user amends a rule, honor the amendment for the whole run.

## Step 3 — Per-batch loop

For each in-scope batch, in plan order:

1. **Invoke `swusim-implement-card`** with the batch's card IDs. It owns the real work: triage (vanilla / keyword-only = verify-only no-ops), look up text, write all DSL tests first (RED), implement, drive the regression to green. Honor its tier gate — no hard stop for Simple/Medium; for a **Hard-tier** card, **defer it to the backlog (Step 5) and keep going** rather than halting the run.
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
3. **Surface the deferral backlog (Step 5):** list each parked card + its one-line reason so the user can decide which to tackle in the pair-programmed phase (or defer further). Re-test each first — some are now unblocked and can just be implemented.
4. Note what you folded in the running summary. Change nothing else.

## Step 5 — Defer vs. halt

Most snags get **deferred, not halted** — keep the autonomous run moving and surface them in a batch later.

**DEFER to the backlog and keep running** (the common case):
- a card is **Hard-tier**, or its ruling is ambiguous and the dictionary/CR doesn't settle it;
- a card needs a **self-contained design choice** that only affects that one card.

Maintain a running **deferral backlog** — CardID + one line on *why* deferred (the ruling question / the design fork). Append the card to the set tracker's `### Already Done` line only once it's actually done; deferred cards stay OFF that line and ON the backlog. Surface the whole backlog at the next retro checkpoint (Step 4) and again at finish (Step 6) so the user gets one consolidated list to clear together. ⚠ Re-test each deferred card when you reach the backlog — infrastructure built by a *later* batch often unblocks it, so it may now be Simple (this happened repeatedly clearing the LAW backlog).

**HALT the run and ask** only when continuing is actually blocked:
- a batch needs **new shared infrastructure with a real design choice** that the *rest of the in-scope cards depend on* — flag that specific fork before the dependent cards;
- you're **stuck too long on one card** — don't grind, ask for help;
- you'd otherwise **modify an existing confirmed test** — ask first.

Everything else is yours to handle: a wrong EXPECT, a fixture's aspect cost, a misdiagnosed "harness" failure — fix and continue.

## Step 6 — Finish

When the scope is complete, report **start → end regression counts**, the phases/batches done, the retros folded, and **the remaining deferral backlog** (the parked Hard/ambiguous cards + why) so the user knows exactly what's left and why. The set is **not** card-complete while the backlog is non-empty — say so plainly. Remind the user the tree is **uncommitted** (they commit manually). If they're wrapping up the session, invoke **`swusim-session-close`** to update project memory.

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
| Halting the whole run on a Hard / ambiguous card | **Defer it to the backlog and keep going** (Step 5). Only halt for a blocker the rest of the scope depends on, or being stuck. Surface the backlog at the checkpoints. |
| Calling a set "card-complete" with a non-empty backlog | It isn't. Report the backlog; run `swusim-set-validation` before any complete claim. |
| Marking a card Done on happy-path coverage | The bar is 98%, and it is a COVERAGE matrix, not a vibe. The most-missed cell is the **negative** — the case proving a condition is load-bearing (the clause must NOT fire when its gate is false). Second-most-missed: the same ability reached by a DIFFERENT dispatch path (as an upgrade, as a token, relocated, deployed-vs-front) and persistence across an arena move / control change / request boundary. Run the adversarial audit before Done: re-read the printed text cold, list what an independent auditor would test, diff against your sections. See the `swusim-implement-card` "coverage matrix". |
| Marking a leader Done on its front side alone | A leader has two ability sets (front + deployed `deployTextData`); each must independently clear 98%. Verify the deployed On Attack / When Deployed / passive / deployed Action has a real handler, not just the front Action. See `SWUSim/docs/leader-gaps.md`. |
| Implementing a clause but getting its TARGET SET / OPTIONALITY wrong | "another X unit" (no "friendly") is enemy-legal too; "up to N" / "you may" needs a decline + heal-less (MAY-choose, not mandatory-choose); a zero-effect target must be unselectable; "can't attack/ready" must apply at SELECTION time. See the `swusim-implement-card` "Recurring bug shapes" block. |
| Shipping a "do-nothing / stacking / stale" bug | Name-a-card vs an EMPTY opponent hand must skip the prompt; "opponent may pay N" the opponent can't afford must auto-resolve; a self-hand discard must exclude the in-flight event (still in ZoneSearch as removed); a When-Defeated target-choose reading `$self` by positional mzID is STALE after cleanup; a repeatable "+X/+Y this phase" buff DE-DUPES unless each application gets a unique token; an upgrade printed "attach to a unit" is enemy-attachable. See the `swusim-implement-card` "More recurring bug shapes (SEC …)" block. |
| Shipping an "attacker-death / token-event / cost-flag-scope" bug | A "when a friendly unit's attack ends / on-defeat / deal-excess" whose effect targets OTHER units must fire when the SOURCE unit trades/dies (move the trigger above the attacker-survival gate + capture the marker in combatCtx before expiry); a TOKEN create/attach/consume/return must fire the same observers as a real card AND a token CEASES on leaving play (never to hand); a "first/next X this phase" cost-flag must be set on ANY qualifying play (not only while controlling the card); a prevention/cap must skip UNPREVENTABLE (indirect + `_SWUDamageUnpreventable`) damage; a "only friendly non-leader unit" grant must re-check the subject isn't itself a leader unit; Support lends WhenAttackEnds abilities too. See the `swusim-implement-card` "More recurring bug shapes (ASH …)" block. |
| Shipping a "request-boundary / bespoke-path" bug — **a green suite cannot see either** | Any value written before an `AddDecision` and read by the handler behind it MUST be serialized (ride the CUSTOM `Param` or an SWUVar) — an in-memory global is EMPTY in the next request, and the handler usually returns silently, so the card just disappears (JTL_094 Luke was neither rebuilt nor discarded in real games). Guard every such flow with a `SimulateRequestBoundary` section, and add any new transient global to `simulateRequestBoundary()` or it becomes untestable. Likewise, a bespoke move/attach/create path must re-do the whole ceremony: fire host reactions, `CollectOnAttachedTriggers`, **and `FlushEntryTriggerBag`** — `AddTrigger` only BAGS a trigger; without the flush it never surfaces. See the `swusim-implement-card` "More recurring bug shapes (JTL re-validation …)" block. |
| Shipping a "payment capacity / generated-field / single-flush-point" bug | Gate a "you may pay N" on **`SWUTotalPaymentCapacity()`** (ready resources + Credits + SEC_122 Droids), never a bare `SWUResourceCount` — otherwise a player who can pay is never offered the ability. A generated `Add<Zone>` accessor silently stringifies any field type the generator lacks a branch for (`array[string]` → the literal `"Array"`, wiping every TurnEffect on an arena move) — fix the GENERATOR and regenerate, and treat any `Array to string conversion` warning as data loss, not noise. A parked/deferred bag drained from only ONE call site strands work on every other path. |
| Shipping a "leave-play observer / control-transfer / leader-unit-status / reduced-deploy" bug | EVERY leave-play path that defeats upgrades (combat, ability-defeat, bounce, AND capture/base-capture) must fire `_SWUOnUpgradeDefeated` — capture was the miss (Zeb ASH_161); taking control of a unit transfers its NON-PILOT upgrades' control (pilots keep theirs) so "friendly upgrade" logic fires for the new controller after an NGOR take-control-then-defeat; a base-prevention (Close the Shield Gate) must skip UNPREVENTABLE damage like the unit path; a card keyed off "a leader unit" must read the LIVE object via `IsLeaderUnit(GetZoneObject($d['mzID']))` not printed CardType (Darksaber/pilot-made leaders — Pellaeon ASH_093); a leader with a reduced deploy threshold (Bo-Katan ASH_010 = resources + Mandalorian units) needs its own `SWUDeployLeader` branch. See the `swusim-implement-card` "More recurring bug shapes (ASH validate-tests re-run …)" block. |
