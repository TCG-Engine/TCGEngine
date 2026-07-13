# Phase 5 — A base reaching 0 HP eliminates that seat instead of ending the game

# P1's 3-power unit attacks P3's base (pre-damaged to 27 of 30). The lethal hit must ELIMINATE P3,
# not declare an instant winner — P1 and P2 are still live.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_229:1:0
WithP3Base: SOR_019:27

## WHEN
- P1>AttackGroundArena:0:P3B

## EXPECT
SEATLIVE:3:false
SEATLIVE:1:true
SEATLIVE:2:true
