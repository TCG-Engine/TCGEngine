# JTL_063 Landing Shuttle — When Defeated: You may draw a card. JTL_063 (pre-damaged to 1) attacks
# SOR_046 and dies to the counter; its When Defeated draws a card.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_063:1:3
WithP2SpaceArena: SOR_046:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:0
