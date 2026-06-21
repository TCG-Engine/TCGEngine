# IBH_009 I've Found Them — if no Unit is in the top 3, you draw nothing and discard all 3 revealed
#   cards. (Player confirms none with an empty AnswerDecision.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_009
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:4
P1NODECISION
