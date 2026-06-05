# JTL_130 Timely Reinforcements (event) — Choose an opponent; for every 2 resources they control, create
# an X-Wing token with Sentinel this phase. P2 controls 6 resources → 3 X-Wings, each with Sentinel.

## GIVEN
P1LeaderBase: JTL_007/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_130
WithP1Resources: 5
WithP2Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
