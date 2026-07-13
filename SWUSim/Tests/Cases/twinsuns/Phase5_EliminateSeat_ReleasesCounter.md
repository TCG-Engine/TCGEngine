# Phase 5 — An eliminated seat's held counter returns to center

## GIVEN
CommonSetup: grw
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithActivePlayer: 3

## WHEN
- P3>TakeCounter:blast
- P1>EliminateSeat:3

## EXPECT
BLASTCOUNTER:AVAILABLE
SEATLIVE:3:false
