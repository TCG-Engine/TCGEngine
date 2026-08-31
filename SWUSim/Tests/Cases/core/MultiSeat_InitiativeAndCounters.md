# CORE — initiative claims and the Twin Suns blast/plan counters, and the turns they legitimately skip.
#
# ⚠ THIS FILE EXISTS PARTLY TO DOCUMENT A NON-BUG. Claiming the initiative, and taking a blast or plan
# counter, both mean "I am done for the rest of this round" (CR §12.5.3). The engine implements that by
# auto-passing such a seat every time the turn reaches it — inside `SWUSwapTurnPlayer` itself. So a seat
# that has claimed or taken a counter IS skipped for the rest of the round, and that is CORRECT. Three
# separate "it skipped the next player's turn" reports have come in against 3- and 4-seat tables; these
# sections pin the legitimate skips so the real ones stand out against them.
#
# ⚠ An initiative claimant's auto-passes are deliberately NOT written to the game log (only their claim
# is), so the log shows a seat vanishing from the rotation with no PASS entry. That is expected, and it
# is why the log cannot be used to diagnose turn order.
#
# ⚠ NO `P{n}OnlyActions`: that directive IS a claimed initiative plus SWU_COUNTER_TAKEN on every other
# seat, i.e. exactly the machinery under test.

---

# ThreeSeat_ClaimingInitiativeMovesTheTurnOneSeat
#// A claim is a pass, so it advances the turn exactly one seat — not two, and not zero.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Claim
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
TURNPLAYER:2
PHASE:MAIN

---

# ThreeSeat_ClaimantIsSkippedForTheRestOfTheRound
#// THE LEGITIMATE SKIP. Seat 1 has claimed, so when seat 3 passes the turn must NOT come back to seat 1
#// — it auto-passes and lands on seat 2. Seat 1 has already spent its round.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Claim
- P2>Pass
- P3>Pass
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
PHASEISNOT:MAIN

---

# FourSeat_ClaimantIsSkipped_TurnGoesToTheNextUNCLAIMEDSeat
#// Four seats, seat 1 claims and seat 2 then passes: the turn goes to seat 3. Seat 1 is out of the
#// rotation but seats 2, 3 and 4 still take their turns in order.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Claim
- P2>Pass
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
TURNPLAYER:3
PHASE:MAIN

---

# OnlyOneSeatMayClaimPerRound
#// The counter is global, not per seat. Seat 2's attempt after seat 1 has claimed must be refused and
#// must NOT move the turn — a refused action is not a pass.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Claim
- P2>Claim
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
TURNPLAYER:2
PHASE:MAIN

---

# ThreeSeat_TakingTheBlastCounterMarksTheSeatAndMovesTheTurn
#// Blast/plan counters exist only at 3+ seats. Taking one is also a pass, so it moves the turn one
#// seat, marks the counter as held by that seat, and makes it unavailable to everyone else.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>TakeCounter:blast
## EXPECT
BLASTCOUNTER:P1
PLANCOUNTER:AVAILABLE
TURNPLAYER:2
P1BLASTAVAIL:0
P2BLASTAVAIL:0
P2PLANAVAIL:1

---

# ThreeSeat_ACounterTakerIsSkippedForTheRestOfTheRound
#// The same legitimate skip as an initiative claim, by a different route: seat 1 took a counter, so the
#// rotation runs 2 → 3 → 2 and never returns to seat 1 this round.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>TakeCounter:blast
- P2>Pass
## EXPECT
BLASTCOUNTER:P1
TURNPLAYER:3
PHASE:MAIN

---

# ThreeSeat_EachSeatMayTakeOnlyONECounterPerRound
#// Seat 1 takes the blast counter; the plan counter is still globally AVAILABLE, but seat 1 may not
#// take it — one counter per seat per round. Seat 2, which has taken nothing, still may.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>TakeCounter:blast
## EXPECT
PLANCOUNTER:AVAILABLE
P1PLANAVAIL:0
P2PLANAVAIL:1
P3PLANAVAIL:1

---

# TwoSeat_CountersDoNotExist_UIReportsThemUnavailable
#// Premier has no blast or plan counters — they are a Twin Suns rule. SWUComputeActionsData defaults
#// both to false and only computes them inside `if (SeatCountForGame() > 2)`.
#// ⚠ This section FOUND A FRAMEWORK DEFECT rather than an engine one: the P{n}BLASTAVAIL assertion
#// documented itself as mirroring SWUComputeActionsData but omitted that seat-count gate, so it
#// reported AVAILABLE here. Fixed in SchemaTestRunner; the gate is a no-op for the only other consumer
#// (twinsuns/PhaseC_CounterAvailability.md runs at three seats).
#//
#// ⚠ THE ACTION GATE CANNOT BE TESTED FROM HERE, and that is a harness/production parity gap worth
#// knowing about. Production refuses the action in CustomInput.php ("if (SeatCountForGame() <= 2)
#// break; // premier: no counters"), but SWUTakeCounter itself carries no seat-count guard, and the
#// harness's takeCounter() adapter calls that function DIRECTLY — its own comment says it "Mirrors
#// CustomInput.php" while replicating only the SaveUndoVersion half. So `P1>TakeCounter:blast` at two
#// seats SUCCEEDS in a fixture and is refused in a real game. Asserting either outcome here would
#// describe the harness rather than the engine, so this section stops at the UI flag.
## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
## EXPECT
SEATCOUNT:2
P1BLASTAVAIL:0
P1PLANAVAIL:0
P2BLASTAVAIL:0
P2PLANAVAIL:0
