# JTL_209 It's a Trap (event) — If an opponent controls more space units than you, ready each space unit
# you control. P2 has 2 space units, P1 has 1 exhausted space unit, so it readies.

## GIVEN
P1LeaderBase: JTL_016/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_209
WithP1Resources: 3
WithP1SpaceArena: SOR_237:0:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:READY
