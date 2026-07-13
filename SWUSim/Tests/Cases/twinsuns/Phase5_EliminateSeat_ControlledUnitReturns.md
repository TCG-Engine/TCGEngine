# Phase 5 — A unit the eliminated seat controls-but-doesn't-own goes to its owner's discard

# P3 controls a unit owned by P2 (mind-controlled onto P3's board). Eliminating P3 must send
# that unit to P2's discard, and it must NOT remain on P3's board.

## GIVEN
CommonSetup: grw
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithP3ControlledUnit: SOR_229:2
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:3

## EXPECT
SEATLIVE:3:false
P3GROUNDCOUNT:0
P2DISCARDCOUNT:1
