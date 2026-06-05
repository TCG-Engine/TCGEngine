# SOR_238 C-3PO — match but decline: P1 chooses 2 (matches SOR_095's cost 2), is offered the card,
# and chooses Leave → nothing drawn, the card stays on top of the deck. ("you MAY reveal and draw")

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_238
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:2
- P1>AnswerDecision:Leave

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:0
P1NODECISION
