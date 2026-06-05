# JTL_123 Dogfight — Attack with a unit even if it's exhausted; it can't attack bases this attack. The
# already-exhausted SOR_063 (power 2) attacks the only legal target, the enemy unit SOR_095, for 2.

## GIVEN
P1LeaderBase: JTL_007/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_123
WithP1Resources: 1
WithP1GroundArena: SOR_063:0:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
