# SearchUpgradeDraw
#// SEC_072 Scour the Archives (event, cost 1) — Search the top 8 of your deck for an upgrade, reveal it,
#//   and draw it. The top of deck has one upgrade (SOR_069) among event fillers; P1 picks and draws it.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SEC_072
WithP1Deck: SOR_069
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_069

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DECKCOUNT:7
