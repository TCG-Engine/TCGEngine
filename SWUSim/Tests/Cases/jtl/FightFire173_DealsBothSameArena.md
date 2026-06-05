# JTL_173 Fight Fire With Fire (event) — choose a friendly unit and an enemy unit in the same arena;
# deal 3 to each. Both are SOR_046 (3/7) → each takes 3 and survives. Both choices auto-resolve.

## GIVEN
P1LeaderBase: JTL_012/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_173
WithP1Resources: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3
