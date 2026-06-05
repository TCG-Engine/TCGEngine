# JTL_053 The Ghost — Each other friendly Spectre unit gains this unit's keywords; while The Ghost is
# upgraded it gains Sentinel. With an upgrade attached, The Ghost has Sentinel and shares it to the
# friendly Spectre unit SOR_146 (Zeb).

## GIVEN
P1LeaderBase: JTL_004/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP1GroundArena: SOR_146:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_053
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:CARDID:SOR_146
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
