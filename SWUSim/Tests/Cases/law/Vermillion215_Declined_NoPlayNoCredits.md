# LAW_215 Vermillion — "They MAY play the revealed card." Declining means nothing is played and NO
# Credits are created (the Credit clause is gated on "if they do"). The revealed Battlefield Marine stays
# on top of P1's deck (deck count unchanged), no unit enters play, and neither player gets Credits.

## GIVEN
P1LeaderBase: JTL_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:2
P1CREDITCOUNT:0
P2CREDITCOUNT:0
