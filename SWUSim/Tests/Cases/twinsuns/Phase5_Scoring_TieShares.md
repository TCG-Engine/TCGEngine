# Phase 5 — Tied highest base HP shares the victory

## GIVEN
CommonSetup: grw
WithSeatOrder: 123
WithLiveSeats: 123
myBase: SOR_019
theirBase: SOR_019
WithP3Base: SOR_019
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:3
- P1>ScorePhaseEnd

## EXPECT
GAMEWINNERS:1,2
