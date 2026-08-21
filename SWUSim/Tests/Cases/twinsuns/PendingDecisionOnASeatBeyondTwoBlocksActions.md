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
