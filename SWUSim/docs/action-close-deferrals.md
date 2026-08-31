# Action-close ownership — deferrals

Open questions and deliberately-unfinished items from the autonomous Phase 0–3 run (2026-08-29).
Nothing here blocks the work that shipped: suite **10054/0**, integration **157/157**, ledger
**393 → 119 blocked** (down from 393 *unblocked* double swaps). See `action-close-ownership.md`.

---

## 1. `SWU_SUPPRESS_AFTERACTION` (ASH_155 Grogu) — **RESOLVED 2026-08-31, and the flag is GONE**

**The 2026-08-29 root cause below was WRONG, and being wrong is what kept the hole open.** It read:

> a PASS and an INITIATIVE CLAIM never open an action id. Neither `SWUPassAction` nor
> `SWUTakeInitiative` goes through `SaveUndoVersion`, so at the bonus attack's close the gate sees
> `id=''` and short-circuits to "allow". The ledger does not model passes at all.

`SWUTakeInitiative` is not reached directly. `CustomInput.php` calls `SaveUndoVersion($playerID)`
**immediately before it**, and the same-day parity fix gave `GameTestAdapter::takeInitiative` a
matching `_SWUOpenAction()`. So a claim opens an id on both routes. Instrumented on the four-seat
ASH_155 fixture:

```
[TRACE] PASS p=1 id='1' closed=''                    ← the claim opened id 1; the pass swapped and
                                                       never closed it
[TRACE] gate phase=MAIN depth=0 id='1' closed=''     ← the bonus attack finds it still OPEN, so the
                                                       gate ALLOWS a second swap: 2 → 3
```

The missing piece was never the OPEN. It was the **CLOSE**. That made the fix small and local, not
the "behaviour change touching the initiative and phase-exit logic" this section had feared.

### What shipped

| | before | after |
|---|---|---|
| the pass's stamp | none — the id stayed open | `_SWUStampActionClosedForPass()` at the top of `SWUPassAction`, both branches |
| the turn swap | gated | unchanged, still gated |
| `PASS = 0` | ran **above** the gate, so a REFUSED frame still wiped the streak | moved under the gate into `_SWUEndActionAndPassTurn()`, used by both gated call sites |
| ASH_155 | one-shot `SWU_SUPPRESS_AFTERACTION` | **removed** — the card sets nothing |

**Ending an action turned out to be TWO things, and only one of them was gated.** With the flag
removed and the stamp added, the swap was correctly refused and the section still failed:

```
[ACTION-LEDGER] BLOCKED-DOUBLE-CLOSE                  ← swap refused …
[TRACE] afterAction p=2 resets PASS from '1' turn=1   ← … streak wiped anyway
```

which leaves the phase in MAIN with `PASS=0` after the exit was already deferred to
`SWU_RETRY_ENDPHASE` — "a fresh action sequence, letting BOTH players keep acting", exactly what
`SWUPassAction`'s own comment warns about. Both halves are now owned by the closing frame.

### The coverage claim from 2026-08-29 no longer holds

That pass concluded "nothing in 10054 sections covers this interaction" after removing the flag left
the suite green. That was true, and it was a **fixture-shape** problem, not a coverage-effort problem:

```
[T] swap -> 2 ; claimantAutoPass=no     ← the claim's own pass
[T] swap -> 1 ; claimantAutoPass=YES    ← the wrongly-allowed second close lands on the CLAIMANT,
[T] swap -> 2 ; claimantAutoPass=no       whom _SWUSeatTookCounterThisRound auto-passes — a THIRD
                                          swap, straight back to where it should be
```

At two seats a correct compensation absorbs the extra swap and the board is indistinguishable. At
three or more, step 2 lands on a seat that has not claimed, nothing auto-passes, and the turn stops
there — the skip. **No Premier-shaped fixture could ever have caught this**, which is why the
detectors live in `Tests/Cases/core/ActionClose_PassEndsTheAction.md` at 3 and 4 seats, with the
2-seat section kept explicitly as a green control that documents its own blindness.

Mutation-verified, each half separately:

| mutation | reds |
|---|---|
| drop `_SWUStampActionClosedForPass()` | 4 sections — the 3P/4P claim sections (`expected turn player 2, got 3`) and the 3P streak section |
| put `PASS = 0` back above the gate | 3 sections — both streak sections (`expected phase RES, got MAIN`) and `Grogu_YesYesYes.md::Interactions_P1Pass_P2Attack` |

Suite after: **10162 / 0**.

### Still true, and still the general limit

`SWUWithNestedActionFrame` cannot express this. `BeginSWUAttack` is asynchronous — it queues and
returns, so the frame has exited by the time `_SWUCombatFinishAction` reaches the after-action
(measured at depth 0). Depth is a within-request property; anything finishing in a later drain needs
the persisted stamp. What changed is that the stamp is now the **ledger's**, not a per-card flag's.

⚠ **Do not reintroduce a per-card suppressor.** If a card needs its trigger not to end the action,
the gate is where that belongs. A hand-placed flag is correct for exactly the card it is written on
and silently wrong for the next one — and wrong only at 3+ seats, where no fixture was looking.

## 2. `SWU_PLOT_IN_PROGRESS` — RESOLVED, and it was half wrong

I had written "it is not a close-ownership mechanism at all, left as-is". The redirect in
`SWUAfterAction` is indeed pure orchestration — it means *"this action is not over yet"*, which is a
different statement from *"someone else owns the close"*. That half stands, and it stays.

**But auditing it turned up a real gap.** `_SWUPlotReoffer` ended the Plot window with a **direct
`SWUSwapTurnPlayer()`**, bypassing the gate — so the deploy action was never stamped closed and a later
stray close could swap again.

An audit of every direct `SWUSwapTurnPlayer()` call found exactly **three**:

| site | status |
|---|---|
| `SWUAfterAction` | gated (correct) |
| `SWUPassAction` | ungated — the §1 hole, passes are not modelled at all |
| `_SWUPlotReoffer` | ungated — **fixed**, now `if (_SWUActionCloseGate()) SWUSwapTurnPlayer();` |

Behaviour is unchanged in the normal case (the gate grants a close that has not happened yet), but the
action is now stamped and a duplicate is refused. 145 Plot-touching sections green; full suite 10054/0.

## 3. Two engine `ActivateCard` sites left unwrapped, on purpose

`SWUWithNestedActionFrame` was applied to the engine's nested-play sites. Three were deliberately NOT
wrapped because the play **is** the player's own action, driven from `CustomInput` which has already
stamped the open:

- `SWUSmuggleResource` (`GameLogic.php`)
- `SWUPlayFromDiscard` (`GameLogic.php`)
- `_SWUForeignDiscardPlayAsUnit` (`GameLogic.php`) — this one I *did* wrap first, and it broke the
  stolen-AT-Hauler family (5 tests). Unwrapped, with a comment recording why.

### `SWUDispatchDroidContinuation` — RESOLVED by user ruling (2026-08-29)

**Your ruling:** droid payment pays *costs*, and the context varies:

| context | example | is it an action? |
|---|---|---|
| playing a card | — | **yes, an action** |
| a When Played ability | TWI_212 | part of the play's trigger |
| a phase-trigger payment | SOR_193 | a tax during **regroup** — no action at all |
| a trigger tax, any time | JTL_192 | during regroup, **or** readying via an event (SOR_169) |
| paying for cards from a separate trigger | LAW_144 | part of that trigger |

So the continuation is **context-dependent, and must not impose an answer either way** — which means
leaving it unwrapped was right, but for a better reason than "I wasn't sure".

Both `ActivateCard` sites in it (`PLAY_CARD`, `PLOT_PAY`) resolve in a LATER request than the context
that armed them, so the depth global is already gone by then (see §1). The **persisted close-stamp is
what carries the context across**: if the originating action already closed, the play's close is
blocked; if it has not, the play closes it. That is exactly the context-dependence your list describes,
and it falls out of the gate without a per-context flag.

**ACTED ON — the regroup risk was real, and it was a determinism bug.** Instrumenting every close
attempt outside the MAIN phase found three, all during regroup (Bounty collected mid-regroup, and the
droid-paid trigger taxes your ruling describes). Each showed `id=` **empty** — but only because those
fixtures never opened an action. In a REAL game the action id carries over from the last action of the
action phase and equals `SWU_ACTION_CLOSED`, so the same code takes the opposite branch:

| | test fixture (no action ever opened) | real game (id carried over) |
|---|---|---|
| gate sees | `id=''` → short-circuits to **allow** | `id == closed` → **refuse** |
| result | swaps the turn during regroup | no swap |

Same code, opposite behaviour, decided by fixture shape — which also means the tests could never have
caught it. **Fixed by pinning the gate to the phase:** an action can only end during MAIN, so a close
attempted during regroup is refused outright regardless of leftover state. Suite 10054/0.

⚠ **Not yet covered by a test.** The three existing regroup sections pass either way for the reason
above; discriminating needs a fixture that takes a real action FIRST and then reaches regroup. Worth
adding, and cheap — noted alongside §7.

⚠ Also recorded from your ruling: droid payment can pay for Emergency Powers, but **does NOT count as
"a resource paid this way"**. That is the existing two-classes rule in the Credit/Droid payment
architecture, unchanged by any of this work.

## 4. The remaining blocked closes — RESOLVED, and the metric was wrong

**Attributed (2026-08-29, backtrace on every blocked close, full suite):**

| source | count | verdict |
|---|---|---|
| decision-queue continuations (`ExecuteStaticMethods` → a queued handler) | **104** | correct — the async class |
| `SWUDispatchDroidContinuation` → `_SWUFinalizeUpgradeAttach` | 3 | correct — async, see §3 |
| `_SWUCombatFinishAction` | 2 | correct — async |

**My hypothesis was wrong.** I had assumed a chunk of these were "nested plays not yet wrapped, blocked
by the stamp rather than suppressed by depth". There are essentially none — Phase 1/2 wrapped them. What
remains is almost entirely work that finishes in a LATER drain, where depth is already gone and the
persisted stamp is the *only* mechanism that can reach it (§1).

**So zero was never the right target.** The ledger counts closes the gate PREVENTED, not bugs remaining.
Driving it to zero would mean removing the guard that is doing the work. The real metric is:

> **double closes that reach the turn = 0** — which is already true, and is what `NOEXTRAACTION` and the
> `TURNPLAYER` sections assert.

`action-close-ledger-baseline.txt` still holds the original 393 for diffing, but read it as "393 double
swaps that USED to land, now 109 caught and refused" rather than as a countdown. A sudden CHANGE in the
count is the signal worth watching, not the count itself.

## 5. Test workaround removed — VERIFIED

`Tests/Cases/lof/ShienFlurry.md::PreventPersistsToLaterDamage` had a `P1>Pass` whose own comment said it
"reconciles the harness's turn accounting after the nested Ambush attack" — i.e. it was compensating for
the double close. With the gate the turn is already correct, so the extra Pass handed it BACK to P1 and
P2's attack never happened (Plo took 0 instead of 2). Line deleted.

**Checked that the section still discriminates** rather than assuming it: mutating the prevent-2 grant
(`$gPlayGrantPrevent2 = true` → `null`) reds it, 1/4. And `DAMAGE:2` can only land if P2's attack
actually resolved, so the section is a receipt for the TURN ACCOUNTING as well as for the prevention —
which is precisely what the deleted line was interfering with.

This was still a change to an existing confirmed test made during an autonomous run, so it remains
flagged for your eyes even though it verifies clean.

## 6. Harness/production divergence found — worth a wider audit

`GameTestAdapter` calls `SWULeaderAction` / `SWUBaseAction` / `SWUUnitAction` / `SWUDeployLeader`
**directly**, while production reaches them through `CustomInput.php`, which calls `SaveUndoVersion()`
and therefore opens an action. The adapter now opens one explicitly at those four entry points.

The wider question: **what else does the harness reach by a different route than production?** Anything
that does will silently miss whatever `CustomInput`/`ActionMap` do on the way in. This cost real
debugging time here (an attack and a leader ability both ran under `id=1`), and the same shape could be
hiding other divergences.

## 7. `NOEXTRAACTION` rolled out — and it immediately found the flaw in its own spec

Added beside every `TURNPLAYER` in the five nested-play guard files: **14 sections**, of which **3
failed**. All three were Trap Field sections — the DEFERRED leg.

That is not a bug in the cards; it is a mis-specification of the assertion. `NOEXTRAACTION` means *"no
second close was ATTEMPTED"*, which is stricter than *"no extra action happened"*. The deferred leg
legitimately attempts one — the queued `SWU_TRIGGER_RESUME` reaches `SWUAfterAction` after the outer
effect has already closed, and the gate refuses it. **The attempt is the mechanism working.** Exactly
the same confusion as §4: the ledger counts closes PREVENTED, not bugs remaining.

Resolved by scoping rather than weakening: the assertion stays on the 11 synchronous sections, is
removed from the 3 deferred-leg ones with a comment explaining why, and its semantics are now documented
at the assertion itself in `SchemaTestRunner.php` and in the `swusim-implement-card` row.

**Also still to do (from §3):** a regroup section that discriminates — one that takes a real action
first, THEN reaches regroup, so the carried-over action id is present. The existing regroup sections
pass either way.
