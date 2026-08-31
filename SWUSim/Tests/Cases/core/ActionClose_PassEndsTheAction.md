# CORE — a PASS ends an action, so the close ledger has to own it.
#
# WHAT THIS FILE PINS. Ending an action is TWO things: the turn swap, and `PASS = 0` (dropping the
# consecutive-pass streak that ends the phase). Both used to be reachable by a frame that did not own
# the close, and each one produces a different, separately-visible bug:
#
#   the SWAP   — swapped twice. At 2 seats NextLiveSeat is an INVOLUTION so it lands back on the actor
#                and reads as "I got a free extra action"; at 3+ it advances twice and THE SEAT IN
#                BETWEEN NEVER ACTS. Same defect, two symptoms, and only the 3+ shape can name it.
#   the STREAK — reset by a refused frame. The phase exit had already been deferred to
#                SWU_RETRY_ENDPHASE, so with PASS back at 0 the retry does nothing, the phase stays
#                MAIN, and (in SWUPassAction's own words) "BOTH players keep acting".
#
# ⚠ THE HOLE WAS NOT THAT THE LEDGER COULD NOT SEE A PASS — that is what
# `docs/action-close-deferrals.md` §1 claimed, and it is STALE. An initiative claim genuinely DOES open
# an action id (`CustomInput.php` calls SaveUndoVersion before SWUTakeInitiative; GameTestAdapter
# mirrors it with `_SWUOpenAction`). Traced 2026-08-31 on the four-seat ASH_155 fixture:
#
#     [TRACE] PASS p=1 id='1' closed=''                    ← claim opened id 1; the pass swapped and
#                                                            never closed it
#     [TRACE] gate phase=MAIN depth=0 id='1' closed=''     ← the bonus attack finds it still OPEN, so
#                                                            the gate ALLOWS a second swap (2 → 3)
#
# The id was always there; nothing ever CLOSED it. `SWUPassAction` now stamps it, and the `PASS = 0`
# reset moved under the same gate into `_SWUEndActionAndPassTurn()`.
#
# ⚠ WHY THIS REPLACED A PER-CARD FLAG. ASH_155 Grogu used to set a one-shot `SWU_SUPPRESS_AFTERACTION`
# consumed in SWUAfterAction. That worked for Grogu and for nothing else: the next "when you take the
# initiative" card whose trigger reaches an after-action would have been silently wrong, at 3+ seats
# only, in a way no 2-player fixture can show. The sections below are written against the MECHANISM so
# they keep holding when that card is added.
#
# ⚠ NO `NOEXTRAACTION` ANYWHERE HERE, deliberately. It means "no second close was ATTEMPTED", and the
# whole point of this fix is that the second close IS attempted and REFUSED — every Grogu section now
# reports one. `TURNPLAYER` and `PHASE` are what distinguish refused from allowed.
#
# ⚠ NO `P{n}OnlyActions` EITHER: it claims initiative and marks every other seat SWU_COUNTER_TAKEN so
# they auto-pass, which is exactly the rotation being measured.
#
# ASH_155 Grogu ("When you take the initiative: you may attack with a unit") is the only card in the
# corpus whose trigger reaches SWUAfterAction from inside a pass window, so it is the probe. What is
# under test is the ledger, not the card — Tests/Cases/ash/Grogu_YesYesYes*.md cover the card itself.

---

# Control_PlainPass_MovesTheTurnExactlyOneSeat
#// The floor. Nothing triggers, so this measures the pass rotation alone. If it ever reds, every
#// section below is reporting a rotation defect rather than a close-ownership one.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Pass
## EXPECT
SEATCOUNT:4
TURNPLAYER:2
PHASE:MAIN

---

# Control_InitiativeClaimWithNoTrigger_MovesTheTurnExactlyOneSeat
#// The claim path on its own. This is the action that OPENS the id the sections below are about, so
#// pinning it separately means a failure there cannot be blamed on the claim itself. Nothing closes
#// the id here except the pass's own stamp.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
## WHEN
- P1>Claim
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
TURNPLAYER:2
PHASE:MAIN

---

# TwoSeat_TriggerInsideTheClaim_IsBLINDToTheDoubleSwap
#// ⚠ THIS SECTION IS A CONTROL, NOT A DETECTOR, AND THAT IS THE FINDING. It stays GREEN under the
#// mutation that reds its three- and four-seat twins — measured, not assumed. Do not "strengthen" it;
#// it is here to record that two seats cannot see this bug at all.
#//
#// WHY, traced under the mutation with the swap instrumented:
#//     [T] swap -> 2 ; claimantAutoPass=no     ← the claim's own pass
#//     [T] swap -> 1 ; claimantAutoPass=YES    ← the bonus attack's wrongly-allowed second close …
#//     [T] swap -> 2 ; claimantAutoPass=no     ← … lands on the CLAIMANT, whom
#//                                               _SWUSeatTookCounterThisRound auto-passes, swapping
#//                                               a THIRD time straight back
#// So an existing, correct compensation absorbs the extra swap and the turn ends where it should. At
#// three or more seats step 2 lands on a seat that has NOT claimed, nothing auto-passes, and the turn
#// stops there — which is the skip. A 2-player fixture is structurally incapable of telling the two
#// apart, so no amount of coverage in Premier-shaped tests would ever have found this.
## GIVEN
CommonSetup2P: rrk/rgw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0
## EXPECT
SEATCOUNT:2
P2BASEDMG:3
TURNPLAYER:2
PHASE:MAIN

---

# ThreeSeat_TriggerInsideTheClaim_DoesNotEatTheNextSeat
#// THE DISCRIMINATOR at its smallest. The claim is a pass, so the turn moves exactly one seat: 1 → 2.
#// A second swap lands on 3 and seat 2 never gets its turn — the "it skipped the next player" report
#// shape, and the thing no 2-player fixture in the suite can express.
## GIVEN
CommonSetup3P: rrk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P1>AnswerDecision:myGroundArena-1
#// ⚠ SEAT-TAGGED. At 3+ seats the attack-target pool is p{n}… — `theirBase-0` names nothing here.
- P1>AnswerDecision:p2Base-0
## EXPECT
SEATCOUNT:3
P2BASEDMG:3
TURNPLAYER:2
PHASE:MAIN

---

# FourSeat_TriggerInsideTheClaim_DoesNotEatTheNextSeat
#// Same at four seats, where a skip is furthest from a wrap and so hardest to reach by accident.
## GIVEN
CommonSetup4P: rrk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:p2Base-0
## EXPECT
SEATCOUNT:4
P2BASEDMG:3
TURNPLAYER:2
PHASE:MAIN

---

# FourSeat_TriggerDECLINED_StillMovesTheTurnExactlyOneSeat
#// The decline branch of the same "may". A fix that only covered the accept path would leave this one
#// skipping, and the decline is the commoner line in real play.
## GIVEN
CommonSetup4P: rrk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P1>AnswerDecision:-
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
TURNPLAYER:2
PHASE:MAIN

---

# TwoSeat_TriggerInsideTheFINALPass_KEEPSThePassStreak
#// THE OTHER HALF OF THE FIX, and it is invisible to every TURNPLAYER assertion above.
#// P1 passes (streak 1), then P2's claim IS the streak-closing pass — but the phase exit cannot fire
#// while Grogu's offer is pending, so SWUPassAction defers it to SWU_RETRY_ENDPHASE and KEEPS PASS=1.
#// The bonus attack then reaches SWUAfterAction. Its swap is correctly refused; if the `PASS = 0`
#// reset is not ALSO under the gate it runs anyway, the retry finds no streak, and the phase stays
#// MAIN with both seats free to act again.
#// The board here proves the exit really fired: the game is in the regroup's RESOURCE step.
## GIVEN
CommonSetup2P: ygk/rgw
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: ASH_155:1:0
WithP2GroundArena: SOR_046:1:0
#// ⚠ DECKS ARE LOAD-BEARING IN EVERY SECTION THAT ACTUALLY REACHES REGROUP. The regroup draws two
#// cards per seat, and an empty deck deals 3 to that seat's own base per missed draw (traced: the
#// first draft read P1BASEDMG 9, which is 3 from the attack plus 6 of deck-out). That is fixture
#// noise, not an engine defect — but it makes any base-damage assertion here meaningless without it.
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046
## WHEN
- P1>Pass
- P2>Claim
- P2>AnswerDecision:myGroundArena-1
## EXPECT
INITIATIVECOUNTER:P2_CLAIMED
PHASE:RES

---

# ThreeSeat_TriggerInsideTheFINALPass_KEEPSThePassStreak
#// The same claim, now needing a streak of TWO to end the phase — the count comes from
#// `count(GetLiveSeatsArray()) - 1`, so a three-seat table exercises a streak length a 2-player
#// fixture cannot produce at all.
## GIVEN
CommonSetup3P: ygk/rgw/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP3GroundArena: ASH_155:1:0
WithP3GroundArena: SOR_046:1:0
#// Decks for the same deck-out reason as the section above — here for all THREE seats.
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP3Deck: SOR_046 SOR_046 SOR_046 SOR_046
## WHEN
- P1>Pass
- P2>Pass
- P3>Claim
- P3>AnswerDecision:myGroundArena-1
#// ⚠ The target does NOT auto-resolve here the way it does at two seats: seat 3 faces two enemy bases,
#// so the attack parks on a choose. Leaving it unanswered leaves a pending decision, the phase exit
#// stays deferred, and the section would read MAIN for a reason that has nothing to do with the streak.
- P3>AnswerDecision:p1Base-0
## EXPECT
SEATCOUNT:3
INITIATIVECOUNTER:P3_CLAIMED
P1BASEDMG:3
PHASE:RES

---

# CounterTakeAlsoRoutesThroughThePass_AndStillMovesOneSeat
#// Taking a blast/plan counter is the THIRD entry into SWUPassAction (after the plain pass and the
#// claim), and like the claim it is stamped open by CustomInput's SaveUndoVersion. It gets its own
#// section because the stamp added to SWUPassAction runs on this path too: a counter take must still
#// move the turn exactly one seat and must not become unclosable.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>TakeCounter:blast
## EXPECT
BLASTCOUNTER:P1
PLANCOUNTER:AVAILABLE
TURNPLAYER:2
PHASE:MAIN

---

# AfterAPass_TheNEXTSeatsRealActionStillCloses
#// THE REGRESSION GUARD FOR THE FIX ITSELF. Stamping an id closed at a pass is only safe if the next
#// real action opens a FRESH one — otherwise every action after any pass would inherit a
#// closed id, have its close refused, and the turn would freeze on that seat forever. That failure
#// would be far worse than the bug being fixed, and it is silent: the action still resolves.
#// Seat 1 passes, seat 2 plays a unit, and the turn must go on to seat 3.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP2Hand: [SOR_128]
WithP2Resources: 8
## WHEN
- P1>Pass
- P2>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
TURNPLAYER:3
PHASE:MAIN

---

# TwoPassesInARow_EachStampsItsOwnAction
#// The same guard for consecutive passes: seat 1's stamp must not make seat 2's pass a no-op. Three
#// seats so the phase does not exit on the second pass and the rotation stays observable.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Pass
- P2>Pass
## EXPECT
TURNPLAYER:3
PHASE:MAIN
