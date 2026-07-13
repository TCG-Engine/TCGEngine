# Phase 5 — Final Showdown eliminates the loser, not "opponent wins"

# Seat 3 carries the SWU_SHD208_LOSE marker. At the start of the regroup phase the Final Showdown
# lose-check fires: in Twin Suns P3 is ELIMINATED (not "P1/P2 wins outright") — P1 and P2 stay live.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithP3GlobalEffect: SWU_SHD208_LOSE
WithActivePlayer: 1

## WHEN
- P1>RunRegroupStart

## EXPECT
SEATLIVE:3:false
SEATLIVE:1:true
SEATLIVE:2:true
