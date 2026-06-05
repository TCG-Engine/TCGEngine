# JTL_053 The Ghost — while NOT upgraded it has no Sentinel, so it shares nothing: the friendly Spectre
# SOR_146 does not gain Sentinel.

## GIVEN
P1LeaderBase: JTL_004/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_053:1:0
WithP1GroundArena: SOR_146:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
