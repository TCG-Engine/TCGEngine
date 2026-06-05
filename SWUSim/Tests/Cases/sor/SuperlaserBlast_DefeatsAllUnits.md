# SOR_043 Superlaser Blast (event, cost 8) — "Defeat all units." Every unit across both players' ground
# and space arenas is defeated simultaneously; the event goes to discard.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_043
WithP1Resources: 8
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1DISCARDCOUNT:3
P2DISCARDCOUNT:2
