# CORE — the per-round locks must CLEAR at the start of the next round.
#
# ⚠ WHY THIS FILE IS THE HIGHEST-VALUE ONE IN THE MULTI-SEAT SET. Two mechanisms take a seat out of the
# rotation for the rest of a round, and both do it by auto-passing that seat inside `SWUSwapTurnPlayer`:
# claiming the initiative, and taking a blast/plan counter (CR §12.5.3). Both are CORRECT within their
# round. `ActionPhaseStart` clears them — `SetInitiativeCounter("P{n}_UNCLAIMED")`,
# `SetSWUVar('SWU_COUNTER_TAKEN', '')`, `SetBlastCounter/SetPlanCounter("AVAILABLE")`, `PASS = 0`.
#
# **If any of those clears failed, the affected seat would be skipped for the REST OF THE GAME** — and
# the symptom a player would report is exactly "it skipped the next player's turn", which is the report
# this engine has now received three times against 3- and 4-seat tables. Nothing in the suite pinned the
# clears at 3+ seats before this file: `twinsuns/RegroupAllSeats.md` covers the regroup's draw, resource
# and trigger steps, but not the lock resets.
#
# ROUND-ROLLOVER IDIOM, since it is fiddly: every live seat passes to end the action phase, then every
# live seat answers the regroup's "Resource up to 1 card" prompt. That prompt is an MZMAYCHOOSE, and
# CommonSetup deals no hand, so the answer is a DECLINE (`AnswerDecision:-`) — `ResourceHand` fails
# there because there is no card to pick.
#
# ⚠ NO `P{n}OnlyActions`: it is itself a claimed initiative plus SWU_COUNTER_TAKEN on the other seats.

---

# ThreeSeat_CounterLockCLEARSAtTheStartOfTheNextRound
#// Seat 1 takes the blast counter in round 1 (locking itself out for that round). After the rollover
#// both counters are back in the centre, nobody is marked as having taken one, and the consecutive-pass
#// streak is zero.
## GIVEN
CommonSetup3P: grw/ggk/ggk
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Deck: SOR_095
WithP3Deck: SOR_095
WithP3Deck: SOR_095
## WHEN
- P1>TakeCounter:blast
- P2>Pass
- P3>Pass
- P1>AnswerDecision:-
- P2>AnswerDecision:-
- P3>AnswerDecision:-
## EXPECT
PHASE:MAIN
BLASTCOUNTER:AVAILABLE
PLANCOUNTER:AVAILABLE
SWUVAR:SWU_COUNTER_TAKEN:
SWUVAR:PASS:0

---

# ThreeSeat_ASeatThatTookACounterIsNOTSkippedTheNextRound
#// THE SECTION THAT MATTERS. Same line as above, and then seat 1 actually takes its round-2 turn: it is
#// the turn player, and its pass moves the turn to seat 2 like any other seat's. A leaked
#// SWU_COUNTER_TAKEN would auto-pass seat 1 on sight and the turn would already have walked past it —
#// permanently, for every remaining round of the game.
## GIVEN
CommonSetup3P: grw/ggk/ggk
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Deck: SOR_095
WithP3Deck: SOR_095
WithP3Deck: SOR_095
## WHEN
- P1>TakeCounter:blast
- P2>Pass
- P3>Pass
- P1>AnswerDecision:-
- P2>AnswerDecision:-
- P3>AnswerDecision:-
## EXPECT
PHASE:MAIN
TURNPLAYER:1
P1BLASTAVAIL:1
P1PLANAVAIL:1

---

# ThreeSeat_TheTurnSWAPSONTOTheUnlockedSeat
#// ⚠ THE AUTO-PASS FIRES ON THE SWAP *ONTO* A SEAT, so a section where the unlocked seat is already
#// the turn player cannot see a stale lock at all — an earlier draft did exactly that and stayed green
#// under the mutation, proving nothing. The turn has to arrive at seat 1 from somewhere.
#// After the rollover: seat 1 passes (streak 1), seat 2 passes (streak 2), then seat 3 takes a real
#// ACTION — which resets the streak instead of ending the phase and swaps the turn onto seat 1. With
#// the lock correctly cleared the turn STOPS there; with a leaked SWU_COUNTER_TAKEN seat 1 auto-passes
#// on arrival and the turn walks straight past it to seat 2, which is the "it skipped my turn" report
#// shape and would repeat every round for the rest of the game.
#// ⚠ Seat 3 must be able to AFFORD its play, or the play is a SILENT NO-OP and this section measures
#// nothing. CommonSetup3P gives every seat a base and leader, which is the half that used to be
#// missing entirely; the other half is the ASPECT PENALTY, which is real game cost, not a fixture
#// artifact. Seat 3 is dressed 'ggk' (Command/Villainy) here while SOR_046 is Vigilance, so it pays
#// the off-aspect penalty and needs the headroom. Match the seat's code to the card instead if you
#// want it cheap.
## GIVEN
CommonSetup3P: grw/ggk/ggk
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Resources: 12
WithP3Hand: SOR_046
WithP3Deck: SOR_095
WithP3Deck: SOR_095
WithP3Deck: SOR_095
## WHEN
- P1>TakeCounter:blast
- P2>Pass
- P3>Pass
- P1>AnswerDecision:-
- P2>AnswerDecision:-
- P3>AnswerDecision:-
- P1>Pass
- P2>Pass
- P3>PlayHand:0
## EXPECT
PHASE:MAIN
P3GROUNDARENACOUNT:1
TURNPLAYER:1

---

# ThreeSeat_InitiativeClaimUNLOCKSTheClaimantNextRound
#// The other lock, same shape. Seat 1 claims in round 1 — which locks it out for that round and hands
#// it the initiative going into the next. At round 2 the counter must read P1_UNCLAIMED (holder kept,
#// claim released) and seat 1 must be the turn player and free to act.
## GIVEN
CommonSetup3P: grw/ggk/ggk
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Deck: SOR_095
WithP3Deck: SOR_095
WithP3Deck: SOR_095
## WHEN
- P1>Claim
- P2>Pass
- P3>Pass
- P1>AnswerDecision:-
- P2>AnswerDecision:-
- P3>AnswerDecision:-
## EXPECT
PHASE:MAIN
INITIATIVECOUNTER:P1_UNCLAIMED
TURNPLAYER:1

---

# ThreeSeat_TheReleasedClaimantRotatesNormally
#// The claimant, once released, passes the turn one seat like anybody else.
## GIVEN
CommonSetup3P: grw/ggk/ggk
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Deck: SOR_095
WithP3Deck: SOR_095
WithP3Deck: SOR_095
## WHEN
- P1>Claim
- P2>Pass
- P3>Pass
- P1>AnswerDecision:-
- P2>AnswerDecision:-
- P3>AnswerDecision:-
- P1>Pass
## EXPECT
PHASE:MAIN
TURNPLAYER:2

---

# FourSeat_CounterLockClearsForEverySeat
#// Four seats, and TWO different seats take the two different counters — the case where a
#// SWU_COUNTER_TAKEN built by string concatenation ("12", "13"…) could clear one entry and keep the
#// other. Both must be free next round.
## GIVEN
CommonSetup4P: grw/ggk/ggk/ggk
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Deck: SOR_095
WithP3Deck: SOR_095
WithP4Deck: SOR_095
WithP4Deck: SOR_095
## WHEN
- P1>TakeCounter:blast
- P2>TakeCounter:plan
- P3>Pass
- P4>Pass
- P1>AnswerDecision:-
- P2>AnswerDecision:-
- P3>AnswerDecision:-
- P4>AnswerDecision:-
## EXPECT
PHASE:MAIN
BLASTCOUNTER:AVAILABLE
PLANCOUNTER:AVAILABLE
SWUVAR:SWU_COUNTER_TAKEN:
P1BLASTAVAIL:1
P2BLASTAVAIL:1
P1PLANAVAIL:1
P2PLANAVAIL:1
