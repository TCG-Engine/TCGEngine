# JTL_257 Flanking Fang Fighter — without another Fighter, it does not have Raid. (SEC_080 is a Trooper,
# not a Fighter.)

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_257:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_257
P1SPACEARENAUNIT:0:NOTKEYWORD:Raid
