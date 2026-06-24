# JTL_047 Admiral Yularen — When Played: choose a keyword; while in play, friendly Vehicles gain it.
# Choosing Grit, the friendly Vehicle SOR_237 (Alliance X-Wing) gains the Grit keyword.

## GIVEN
P1LeaderBase: JTL_001/SOR_020:3
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Grit

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Grit
