# Phase 5 — The winner set stores one or many seats

## GIVEN
CommonSetup: grw
WithSeatOrder: 1234
WithLiveSeats: 24
WithActivePlayer: 1

## WHEN
- P1>DeclareWinners:2,4

## EXPECT
GAMEWINNERS:2,4
