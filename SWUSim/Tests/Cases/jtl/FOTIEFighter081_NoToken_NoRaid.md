# JTL_081 First Order TIE Fighter — without a token unit in play, it does not have Raid.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_081:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_081
P1SPACEARENAUNIT:0:NOTKEYWORD:Raid
