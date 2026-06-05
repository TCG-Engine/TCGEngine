# JTL_122 All Wings Report In (event) — Exhaust up to 2 friendly space units; for each, create an X-Wing
# token. P1 exhausts both space units and gets 2 X-Wings.

## GIVEN
P1LeaderBase: JTL_007/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_122
WithP1Resources: 1
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0&mySpaceArena-1

## EXPECT
P1SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:1:EXHAUSTED
P1SPACEARENACOUNT:4
P1SPACEARENAUNIT:2:CARDID:JTL_T02
P1SPACEARENAUNIT:3:CARDID:JTL_T02
