# Phase 5 — 4-player: first elimination ends the game at phase end by highest base HP

# P1 (3-power unit) kills P3's base (27/30). P3 is eliminated + P1 heals 5 (already at 0, capped).
# At phase end the game ends: among the live seats P1 (base 30) beats P2 (30-20=10) and P4 (30-10=20),
# winning outright. P2 and P4 are NOT declared winners.

## GIVEN
CommonSetup: grw/ggk/{theirBaseDamage:20}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_229:1:0
WithP3Base: SOR_019:27
WithP4Base: SOR_019:10

## WHEN
- P1>AttackGroundArena:0:P3B
- P1>ScorePhaseEnd

## EXPECT
SEATLIVE:3:false
SEATLIVE:1:true
SEATLIVE:2:true
SEATLIVE:4:true
GAMEWINNERS:1
