# Twin Suns Phase 3: combat damage to a SPECIFIC opponent's base. P1's 3-power unit attacks P3's base
# (both P2 and P3 bases are valid targets → the picker resolves to p3Base-0). The 3 damage must land on
# P3's base, NOT P2's — proving (a) a "p{n}Base" target is not silently skipped (the old code only matched
# the literal "theirBase"), and (b) it routes to the mzID's real owner, not the 2-player OtherPlayer.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_229:1:0
WithP3Base: SOR_019

## WHEN
- P1>AttackGroundArena:0:P3B

## EXPECT
SEATCOUNT:3
P3BASEDMG:3
P2BASEDMG:0
P1NODECISION
