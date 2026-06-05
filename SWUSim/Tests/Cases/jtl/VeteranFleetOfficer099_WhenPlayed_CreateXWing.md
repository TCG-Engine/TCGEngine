# JTL_099 Veteran Fleet Officer — When Played: Create an X-Wing token.

## GIVEN
P1LeaderBase: JTL_007/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_099
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_099
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_T02
