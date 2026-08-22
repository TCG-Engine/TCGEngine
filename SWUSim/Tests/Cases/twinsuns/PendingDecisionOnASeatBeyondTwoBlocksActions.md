# SeatThreeStillResourcing_BlocksP1FromActing
#// BUG REPORT #978 (game 3497, Twin Suns 4-seat): "I as P1 can play my turn 1 card before my other
#// player (P3) is done resourcing."
#//
#// ROOT CAUSE — DecisionQueueController::$numPlayers is the literal 2, declared once and assigned
#// NOWHERE. AllQueuesEmpty() loops 1..$numPlayers, so it structurally CANNOT see a pending decision
#// on seat 3 or 4. Every action/phase gate in the engine asks that one question (TurnController's
#// PENDING_DECISION, ~10 sites in CustomInput.php, SimHistory), so in a Twin Suns game the whole
#// "wait for everyone" interlock silently degrades to "wait for seats 1 and 2": the regroup advances
#// to the next action phase while seat 3 still owes a resource, and seat 1 gets to act first.
#// This is the Twin Suns seat-hardcode family — a 1..2 loop left on a path the 4-seat format now uses.
#//
#// THE DRIVE IS THE REAL REPRO, not a synthetic prompt: ResourcePhase() queues one MZMAYCHOOSE per
#// LIVE seat (correctly, via GetLiveSeatsArray). Seats 1 and 2 answer; seat 3 does not.
#// ⚠ Seat 2 MUST answer. If it were left pending too, the old 1..2 loop would hold the gate shut for
#// the wrong reason and this section would pass against the bug.
#// ⚠ Hand arithmetic: the regroup DRAWS 2 before the resource step, so a correctly-BLOCKED P1 holds
#//   1 + 2 = 3 cards. Asserting 1 here fails against the fix as well as the bug.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
WithSeatOrder: 123
WithActivePlayer: 1
WithP1Hand: SOR_128
WithP1Deck: [SOR_095 SOR_046 SEC_080 SOR_046]
WithP2Deck: [SOR_095 SOR_046 SEC_080 SOR_046]
WithP3Deck: [SOR_095 SOR_046 SEC_080 SOR_046]

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayHand:0

## EXPECT
P3HASDECISION
PHASEISNOT:MAIN
P1HANDCOUNT:3
P1GROUNDARENACOUNT:0

---

# EverySeatResourced_ThenP1CanAct
#// The control, and the half that stops the fix from being "block everything". Identical drive with
#// seat 3 ALSO answering: the regroup completes, the action phase starts, and P1's play goes through.
#// Without this, widening the seat loop could deadlock every Twin Suns game and still look green.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
WithSeatOrder: 123
WithActivePlayer: 1
WithP1Hand: SOR_128
WithP1Deck: [SOR_095 SOR_046 SEC_080 SOR_046]
WithP2Deck: [SOR_095 SOR_046 SEC_080 SOR_046]
WithP3Deck: [SOR_095 SOR_046 SEC_080 SOR_046]

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P1>ResourcePass
- P2>ResourcePass
- P3>ResourcePass
- P1>PlayHand:0

## EXPECT
P3NODECISION
PHASE:MAIN
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_128

---

# TwoPlayerUnaffected_SeatTwoStillBlocks
#// The 2-player regression guard. Seat 2 was always inside the old 1..2 loop, so a correct fix must
#// leave this byte-identical — it is the case the hardcoded 2 got right.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
WithActivePlayer: 1
WithP1Hand: SOR_128
WithP1Deck: [SOR_095 SOR_046 SEC_080 SOR_046]
WithP2Deck: [SOR_095 SOR_046 SEC_080 SOR_046]

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P1>PlayHand:0

## EXPECT
P2HASDECISION
P1HANDCOUNT:3
P1GROUNDARENACOUNT:0

---

# EliminatedSeatsPendingDecisionIsDrained_NoSoftLock
#// ⚠ THE LAST PASS-0 SEAM (2026-08-23). Elimination cleaned up the dead seat's ARENAS and BASE
#// (_SWUEliminationCleanup) but never touched its DECISION QUEUE. Nothing drains an eliminated seat's
#// queue — no player will ever answer it — so anything pending at the moment of elimination stayed there
#// forever, and every "wait for everyone" gate in the engine (AllQueuesEmpty, TurnController's
#// PENDING_DECISION, ~10 sites in CustomInput) blocks on it.
#//
#// ⚠ THIS IS WORSE THAN THE REST OF THE SWEEP. The sweep's usual failure is a LOST trigger — silent, but
#// the game continues. This one HANGS THE TABLE: the survivors can never act again, in a format where
#// elimination is a normal mid-game event. It is the difference between a bug and an unfinishable game.
#//
#// The repro reuses the section above, which is the real drive rather than a synthetic prompt: the
#// regroup queues one resource decision per LIVE seat, seats 1/2/4 answer and seat 3 does not — so seat
#// 3 is holding the interlock shut, exactly as bug #978 described. Seat 3 is then eliminated.
#// Two things must now be true:
#//   • seat 3's pending decision is GONE (the queue was drained by _SWUEliminationCleanup), and
#//   • the table is not stuck — the gate that seat 3 was holding has opened.
#// Also fixed alongside: DecisionQueueController::AddDecision now refuses to queue onto a seat that
#// cannot answer (SWUSeatAcceptsDecisions, resolved via function_exists so other sims are unaffected),
#// so a later-resolving continuation cannot re-lock the table after the drain.
#//
#// ⚠ A 2-player version CANNOT EXIST — SWUEliminateSeat returns immediately below three seats, because
#//   losing your base in Premier ends the game rather than eliminating a seat. The seat count IS the test.
#// Mutation check: remove the queue drain from _SWUEliminationCleanup and P3NODECISION reds.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithP1Hand: SOR_128
WithP1Deck: [SOR_095 SOR_046 SEC_080 SOR_046]
WithP2Deck: [SOR_095 SOR_046 SEC_080 SOR_046]
WithP3Deck: [SOR_095 SOR_046 SEC_080 SOR_046]
WithP4Deck: [SOR_095 SOR_046 SEC_080 SOR_046]
WithP3Base: SOR_019
WithP4Base: SOR_019

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P4>Pass
- P1>ResourcePass
- P2>ResourcePass
- P4>ResourcePass
- P1>EliminateSeat:3

## EXPECT
SEATCOUNT:4
SEATLIVE:3:false
P3NODECISION
