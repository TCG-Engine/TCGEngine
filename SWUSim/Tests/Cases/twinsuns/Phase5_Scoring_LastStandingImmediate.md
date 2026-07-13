# Phase 5 — Dropping to one live seat scores immediately (no phase-end needed)

## GIVEN
CommonSetup: grw
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:2
- P1>EliminateSeat:3

## EXPECT
GAMEWINNERS:1
SEATLIVE:2:false
SEATLIVE:3:false
