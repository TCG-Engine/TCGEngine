# Phase 5 — Eliminating a seat removes its owned units from play

## GIVEN
CommonSetup: grw
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithP3GroundArena: SOR_229:1:0
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:3

## EXPECT
SEATLIVE:3:false
P3GROUNDCOUNT:0
