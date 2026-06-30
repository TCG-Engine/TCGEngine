# SOR_238 C-3PO — whiff: P1 chooses 5, but the top card SOR_095 costs 2 → no match. The player
# STILL gets to look at the top card (the peek always happens — "Choose a number, THEN look at the
# top card"), but the only outcome is to acknowledge and leave it on top: nothing is revealed or
# drawn, and the card stays on top.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_238
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:5
- P1>AnswerDecision:OK

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:0
P1NODECISION
