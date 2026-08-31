# Action-close ownership — root cause and phased plan

**Supersedes `nested-play-extra-action-sweep.md`** (deleted 2026-08-29). That doc treated the
"free extra action" bugs as a nested-play problem and fixed them card by card. The investigation on
2026-08-29 found the actual cause one level down, so the worklist is re-framed here. The sweep's
measured evidence is carried forward below — it is still the best catalogue of how this bug presents.

---

## The root cause: nothing owns the end of an action

`Schemas/SWUSim/TurnSchema.txt` describes an alternating-action model and says *"After the action, game
code swaps TurnPlayer."* The state that was supposed to do that is a stub:

```php
function MainPhase() {
    // TODO: consecutive-pass tracking and TurnPlayer swap
}
```

`SWUSim/Custom/GameLogic.php:5837`. Four findings, each verified rather than inferred:

| finding | evidence |
|---|---|
| **`MainPhase()` does nothing** | the body is the TODO above |
| **`# TurnStyle: PerAction` is inert** | it is a `#` comment, and `zzTurnGenerator.php` contains **zero** occurrences of the string `TurnStyle` — it has never been a directive |
| **The FSM has no action boundary** | MAIN's only action-phase transition is `AUTO -> MAIN`, and generated `AdvanceTurnState` fires only when `$next !== GetCurrentPhase()`, so a self-transition is structurally inert. Instrumented: `MainPhase()` was called **once across two actions** — on entry from `APS`, never per action |
| **The real turn model is 495 hand-written calls** | `SWUAfterAction(` appears **495** times under `SWUSim/Custom/`, **449 of them in card files**. Its contract is owned by every card author individually |

So "one action ends, the turn swaps once" is not enforced anywhere. It is re-derived, by hand, in 449
card files. Every bug in this family is one of those files getting it wrong — which means the supply of
them is unbounded and card-by-card fixes cannot converge.

### Six mechanisms already answer the same question

The clearest evidence that the contract is unowned is how many things exist to suppress it:

| mechanism | refs | what it is |
|---|---|---|
| `SWU_PLOT_IN_PROGRESS` | 8 | redirect: a nested play inside a Plot window must not end the deploy action |
| ~~`SWU_SUPPRESS_AFTERACTION`~~ | 5 | one-shot: ASH_155 Grogu's initiative-triggered bonus attack — **REMOVED 2026-08-31**, replaced by the pass's own close stamp (deferrals §1) |
| `SWU_NESTED_PLAY_OWNS_AFTERACTION` | 8 | the deferred-resume leg (added 2026-08-29) |
| `ownsAA` decision-queue param | 4 | LOF_197 threads ownership through the queue explicitly |
| `SWUEnforceUniqueness` early-returns | 2 sites | re-entrant: `SWUAfterAction` calls itself back after a uniqueness defeat |
| `SWUNestedPlay`'s `$gTurnPlayer`/`PASS` save-restore | 1 helper, ~40 callers | the immediate leg |

Six answers to "who closes this action". None of them can see the others.

---

## ⚠ THE SUITE IS BLIND TO THIS BY CONSTRUCTION — READ BEFORE PLANNING ANY WORK

| | count |
|---|---|
| test files asserting `TURNPLAYER` at all | **33** (98 assertions) |
| test files using `P1OnlyActions` | **1834** |

`P1OnlyActions` claims initiative so the opponent auto-passes, which makes a double turn-swap
*indistinguishable from a single one*. So ~10000 of the 10054 passing tests cannot observe this bug
class, and a green suite is not evidence of anything here. This is why **Phase 0 is observability, not
the fix** — refactoring the turn model behind a blind suite is how a 495-call-site change goes wrong
silently.

---

## The plan

### Phase 0 — Make the bug observable before changing anything

Nothing here changes behaviour. The deliverable is that the *existing* suite starts being able to fail
on a double close.

1. **An engine-level invariant.** Stamp a monotonic action id when an action opens; have `SWUAfterAction`
   record `(actionId → closes)`. Under a harness/debug flag, a second close for the same id is a hard
   error naming the action. This makes every one of the 1834 `P1OnlyActions` files a potential detector
   without editing one of them.
2. **Run the whole suite with the invariant armed** and triage what it catches. Expect latent hits — the
   sweep found bugs on 7 of 13 sites it actually probed. Anything it finds is a pre-existing bug, so
   log it, do not fix it in this phase.
3. **A harness `TURNPLAYER` alternation helper** so a section can assert "exactly one swap happened"
   without hand-building an alternating-turn fixture each time.

**Exit criterion:** the invariant is armed in CI, the suite is green with it, and every hit it produced
is either fixed or logged as a known-red with an owner. Do not start Phase 1 before this.

### Phase 0 RESULTS (2026-08-29) — measured, and they change Phase 1

The ledger is installed **observe-only** (`_SWUOpenAction` / `_SWUCloseActionIsDuplicate` in
`Custom/GameLogic.php`). Action ids are stamped at `SaveUndoVersion`, which is already called at every
user-initiated action — undo granularity IS action granularity.

> **393 double-closes across 127 test files. Suite: 10054 passed, 0 failed.**

Per set: law 68 · sor 66 · hmw 48 · lof 45 · ash 39 · shd 38 · jtl 24 · ts26 21 · sec 20 · twi 8 ·
core 5 · ic27 3. Worst files: `hmw/Osha_HauntedByHerPast` 18 · `law/Vermillion_QirasAuctionHouse` 12 ·
`hmw/TwilekKalikori` 12 · `sor/DarthVader_CommandingTheFirstLegion` 10 · `law/JynErso_TimeToFight` 10.
Full baseline: `action-close-ledger-baseline.txt` (regenerate and diff to see movement).

**Not all 393 are user-visible bugs**, and that is the important nuance. A double close is only a bug
when it produces a net-wrong turn player. `SWUNestedPlay`'s save/restore lets the close happen twice and
then *undoes* the extra swap — so the ledger correctly reports a structural double close where the
player never sees one. Verified on `OneMustDestroyToCreate_NoExtraAction` section 1: it PASSES and still
reports a double close.

That is the whole point: **the second close is not prevented today, only compensated for** — and you
cannot compensate across a request boundary, which is exactly why the deferred leg needed its own flag.

### ⚠ Phase 1 CANNOT be "block the duplicate close" — measured, not predicted

Trialled: `if (_SWUCloseActionIsDuplicate()) return;` before the terminal swap.
**Result: 17/22 of the nested-play guard sections passed — 5 regressions.**

The reason is ordering. For a nested play the sequence is:

| step | what happens | turn |
|---|---|---|
| 1 | inner `ActivateCard` → `SWUAfterAction` → swap | opponent |
| 2 | `SWUNestedPlay` restores `$gTurnPlayer` | me |
| 3 | outer `FINISH_PLAY_CARD` → `SWUAfterAction` → swap | opponent ✓ |

Step 1 is the FIRST close, so a duplicate-blocker suppresses **step 3** — but step 2 already undid
step 1, so the net result is no swap at all. "First close wins" is also semantically wrong on its own
terms: the inner close fires while the outer effect is still resolving, so anything reading TURNPLAYER
mid-resolution would see the wrong seat.

**Phase 1 must therefore be DEPTH-based, not duplicate-based.** The inner close must be suppressed
*because it is nested*, and the outermost frame must be the one that swaps:

- `ActivateCard` (or `SWUNestedPlay`) increments an action-nesting depth on entry and decrements on exit.
- `SWUAfterAction` performs the terminal swap **only at depth 0**.
- Depth lives in the gamestate alongside the action id, so a deferred `SWU_TRIGGER_RESUME` arriving in a
  later request is correctly seen as depth 0 for an action that is already closed — and the *closed* flag
  handles that leg.

So the ledger needs **both** signals: depth (am I nested?) and closed (has this action already ended?).
The 393-hit baseline stays the measurement of success — Phase 1 is done when it reaches 0 without the
save/restore, not when the suite is green, which it already is.

## ✅ SHIPPED 2026-08-29 — Phases 0–3 run autonomously

| | |
|---|---|
| schema suite | **10054 passed / 0 failed** |
| integration | **157 / 157** |
| ledger | **393 unblocked double swaps → 119 BLOCKED** (none reach the turn) |
| mechanisms retired | 3 of 6 (`SWU_NESTED_PLAY_OWNS_AFTERACTION`, the save/restore, `ownsAA`) |

**The gate** (`_SWUActionCloseGate` in `Custom/GameLogic.php`) refuses a close for two independent
reasons — *nested* (within-request depth) and *already closed* (a gamestate stamp that survives the
request boundary). `SWUWithNestedActionFrame(fn() => ...)` marks a nested play; `SWUNestedPlay` uses it.

### What the run actually found — the fix was never the hard part

Three of the four failure clusters were **actions that never opened an id**, not bad suppression:

1. **The harness bypassed production's action-open.** `GameTestAdapter` calls `SWULeaderAction` /
   `SWUBaseAction` / `SWUUnitAction` / `SWUDeployLeader` directly, while production reaches them through
   `CustomInput.php` → `SaveUndoVersion()` → the stamp. An attack and a leader ability both ran under
   `id=1`, so the ability's close was refused as a duplicate and the turn never passed.
2. **SHD_144's compelled attack IS an action** ("its controller's next action this phase must be an
   attack action with that unit") but is performed programmatically, so nothing stamped it.
   `_SWUCheckForcedAttack` now opens one.
3. **Osha and Shien Flurry** — the two documented self-managed exceptions — were compensating for the
   missing gate. Wrapping their raw `ActivateCard` in a nested frame made both ordinary, which was the
   doc's stated success signal for Phase 1.

⚠ **A nested play must be suppressed by DEPTH, not by the stamp.** Both produce a green suite today, but
the stamp version is order-dependent: whichever close runs first consumes it, and the inner one runs
first. That is precisely the trap that broke the first Phase-1 attempt (17/22).

⚠ **Not every engine `ActivateCard` is nested.** `_SWUForeignDiscardPlayAsUnit` looked identical to the
others; wrapping it suppressed the only close a play-from-an-opponent's-discard has and broke the
stolen-AT-Hauler family. `SWUSmuggleResource` and `SWUPlayFromDiscard` are the same shape.

### Open items

`SWUSim/docs/action-close-deferrals.md` — including the one retirement that was reverted because it
could not be verified (`SWU_SUPPRESS_AFTERACTION`: deleting its replacement left all 25 Grogu sections
green, so nothing covered it), and why `SWU_PLOT_IN_PROGRESS` is not a close-ownership mechanism at all.

> **UPDATE 2026-08-31 — `SWU_SUPPRESS_AFTERACTION` is now RETIRED, and the reason it looked
> unverifiable was a fixture-shape artifact, not missing effort.** All 25 Grogu sections were
> two-player, and at two seats the wrongly-allowed second swap lands on the initiative CLAIMANT, whom
> `_SWUSeatTookCounterThisRound` auto-passes — a third swap that puts the turn back where it belonged.
> The defect is only observable at three or more seats. Detectors now live in
> `Tests/Cases/core/ActionClose_PassEndsTheAction.md`; deferrals §1 has the traces.

---

### Phase 1 — The action-close ledger

One place decides whether an action is still open.

- Action id + open/closed state, stored **in the gamestate**, not a global — the deferred leg fires in a
  *later request*, which is exactly what defeats every transient-flag approach (see
  `request-boundary-extra-action-bug-class` in project memory).
- `SWUAfterAction` swaps only if the current action is still open, then marks it closed. A second call
  — immediate or deferred, same request or a later one — no-ops.
- **Add nothing to card files.** All 449 call sites keep working unchanged; that is the point.
- Leave every existing suppression mechanism in place. Phase 1 is additive.

**⚠ Two traps, both already known:**
- `SWUEnforceUniqueness` deliberately early-returns and re-enters `SWUAfterAction` for the *same* action
  (looping for 3+ copy piles). The ledger must not close on those paths, or a uniqueness defeat strands
  the turn.
- `SWUAfterActionExtra` (JTL_018 Kazuda) is the deliberate no-swap variant and is already correct.
  **Do not fold it in.** It intentionally skips `SWU_LAST_ACTION` for SEC_194's sake.

**Exit criterion:** full suite green, Phase 0 invariant green, and the ledger mutation-verified — revert
it and the `NestedPlayGrantsNoExtraAction` / `OneMustDestroyToCreate_NoExtraAction` guards go red.

### Phase 2 — Retire the six mechanisms, one at a time

Only once the ledger is proven. Each removal is its own change with its own green gate, in this order
(easiest to most entangled):

1. `SWU_NESTED_PLAY_OWNS_AFTERACTION` — newest, narrowest, one call site.
2. `SWUNestedPlay`'s save/restore — the helper may collapse to a plain `ActivateCard` call.
3. `ownsAA` (LOF_197) — an explicit param the ledger makes redundant.
4. ~~`SWU_SUPPRESS_AFTERACTION` (ASH_155)~~ — **DONE 2026-08-31.** Retired; the pass now stamps its own close and 3P/4P sections hold it.
5. `SWU_PLOT_IN_PROGRESS` — **last.** Most entangled; a Plot window legitimately keeps one action open
   across several nested plays, so it is the real test of whether the ledger models "open" correctly.

**Rule:** removing a mechanism must RED a test if the ledger is broken. If a removal is silently green
either way, the ledger is not actually covering that case — stop and find out why.

### Phase 3 — Reconcile the card surface

- Re-evaluate the 7 entries in `nested_play_guard_test.php`'s allowlist. With one ledger, Osha and
  Shien Flurry should stop being special cases — that is the concrete signal Phase 1–2 worked.
- Decide what `nested_play_guard_test.php` guards once `SWUNestedPlay` is thin: either re-point it at the
  ledger or retire it. **Do not retire it before Phase 2 is complete** — until then it is the only thing
  stopping a raw `ActivateCard` reappearing.
- Fold the ledger into `swusim-implement-card`, replacing the `SWUNestedPlay` row.

### Phase 4 — The FSM (the design debt, not the bug fix)

Worth doing only once Phases 1–3 mean exactly one place ends an action. Requires generator work:

- `MainPhase()` is a stub; `TurnStyle: PerAction` is unparsed.
- **The generator cannot express a self-transition with work** (`AdvanceTurnState` requires
  `$next !== GetCurrentPhase()`), so "tighten MAIN/PASS" cannot be done in the schema as written. It
  needs a real intermediate state — `MAIN --ACTION--> AEND --AUTO--> MAIN` — or generator support for
  executable self-transitions.
- `EngineTransitionOverride` already exists as a hook and SWUSim already implements it (for
  `_SWUNeedsExtraRegroup`), so there is a seam to build on.

⚠ `TurnController.php`, `GetNextTurn.php` et al. are **generated and gitignored**. The change lives in
`Schemas/SWUSim/TurnSchema.txt` and `zzTurnGenerator.php`, and needs a post-deploy regen on the server.

---

## Carried forward from the sweep — still true, still load-bearing

### The two after-action legs

1. **Immediate** — `ActivateCard` runs its own after-action.
2. **Deferred** — if the played card arms an ENTRY TRIGGER, a `SWU_TRIGGER_RESUME` is queued and
   finalises **in a later drain**, after any save/restore has already run. This is the leg that makes
   transient globals insufficient and the ledger necessary.

⚠ Count resumes **before and after** the play, never "is one pending" — a unit's own When Played is
itself dispatched from a resume, so one is always already queued. Claiming it suppresses the OUTER
finalisation and strands the turn (measured on SOR_102 Home One).

### Testing rules — every one of these was learned the hard way

- **`P1OnlyActions` makes `TURNPLAYER` unobservable.** Assert it on a genuinely alternating turn.
- **HMW_171 Trap Field reaches leg 2** for nearly any "play a unit" card — it reacts to ANY non-leader
  ground unit entering play, either player's, and is owned by the base owner.
- **A "play up to N" card HIDES the bug at EVEN counts.** Two nested closes + the outer one = three
  swaps, landing on the same seat as one. ASH_104 Dathomiri Magicks passed *and* survived mutation until
  it was re-run with a single play. **Use an ODD number of plays.**
- **Assert a unit actually entered play**, not just `TURNPLAYER` — a section that only checks the turn
  passes if the play silently fizzled.

### The exceptions that are real (do NOT bulk-migrate)

| file | why |
|---|---|
| JTL_005 Piett · JTL_003 Lando · JTL_011 Vonreg · SOR_003 Chewbacca · SOR_022 Energy Conversion Lab | leader/base Action DELEGATES its whole action to the play; `ActivateCard`'s after-action is the action's only one |
| HMW_017 Osha | owns its finalisation via `_SWUOsha017CloseAction` (bug 976c) |
| LOF_220 Shien Flurry | owns its finalisation |

Osha and Shien Flurry were migrated wholesale during the sweep and **both broke** — double suppression
left the action unfinished. Shien Flurry surfaced as a DAMAGE assertion, not a turn one, because a
phase-scoped "prevent 2" then never expired. Phase 1 should make both ordinary; if it does not, the
ledger is modelling "open" wrong.

### Sites measured broken and fixed (2026-08-29)

JTL_121 Salvage · SHD_094 Palpatine's Return · SOR_102 Home One · TWI_189 Unnatural Life · ASH_247 One
Must Destroy to Create (bug #997) · ASH_104 Dathomiri Magicks · TS26_57 Mechanize · HMW_204 Nightbrother
· HMW_016 Maul (×2).

**Not probed:** LOF_036 Old Daka — fixed by classification only; needs a friendly *Night* unit fixture.

### Existing guards

- `Tests/Cases/core/NestedPlayGrantsNoExtraAction.md` — 3 sections, both outer contexts
- `Tests/Cases/core/NestedPlayGapFixes.md` — 2 sections
- `Tests/Cases/ash/OneMustDestroyToCreate_NoExtraAction.md` — 4, including the Trap Field line
- `DevTools/tests/nested_play_guard_test.php` — bans raw `ActivateCard` in card files (7 allowlisted)

---

## Explicitly NOT doing

- **Not** folding `SWUAfterActionExtra` into the ledger — Kazuda's no-swap behaviour is correct.
- **Not** migrating more cards to `SWUNestedPlay`. That was the old direction; the ledger removes the
  need. Migrating now is work Phase 2 would undo.
- **Not** starting Phase 4 early. The FSM is the right architecture and the wrong first move.
