# SOR_104 U-Wing Reinforcement (Event, cost 7) — Search the top 10 of your deck for up to 3
# units with combined cost 7 or less and play each for free. The top 10 hold two Battlefield
# Marines (cost 2 each, combined 4 ≤ 7) among event fillers; both are played free into the
# ground arena. The U-Wing event goes to discard.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095,SOR_095

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:0
P1DECKCOUNT:8
P1DISCARDCOUNT:1
