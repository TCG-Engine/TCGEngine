# Phase 5 — Eliminating a seat removes it from LiveSeats (order unchanged)

## GIVEN
CommonSetup: grw
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:3

## EXPECT
SEATCOUNT:3
SEATLIVE:1:true
SEATLIVE:2:true
SEATLIVE:3:false
