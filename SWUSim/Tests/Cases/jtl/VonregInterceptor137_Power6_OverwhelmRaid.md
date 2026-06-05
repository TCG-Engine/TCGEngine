# JTL_137 Vonreg's TIE Interceptor — with Academy Training (+2/+2) and an Experience token (3+2+1=6
# power) it has both Overwhelm (4+) and Raid (6+).

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_137:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP1SpaceArenaUpgrade: 0:SOR_T01

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:POWER:6
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm
P1SPACEARENAUNIT:0:HASKEYWORD:Raid
