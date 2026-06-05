# JTL_176 Shoot Down (event) — the base damage only follows if the space unit is DEFEATED. JTL_069
# (4/7) survives the 3 damage, so no base option is offered.

## GIVEN
P1LeaderBase: JTL_012/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_176
WithP1Resources: 2
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P2BASEDMG:0
P1NODECISION
