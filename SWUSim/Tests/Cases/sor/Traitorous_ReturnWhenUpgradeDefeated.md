# SWUSim Replay Schema
Traitorous — when upgrade is defeated, unit returns to its owner

## GIVEN
P1LeaderBase: SOR_014/SOR_024
P2LeaderBase: SOR_007/SOR_024
SkipPreGame: true
WithP1Hand: SOR_122
WithP2Hand: SOR_251
WithP2GroundArena: SOR_063:1:0
WithP1Resources: 5
WithP2Resources: 3

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2RESCOUNT:3
P2RESAVAILABLE:2
