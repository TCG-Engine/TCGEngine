# SOR_238 C-3PO (Unit 1/4, cost 2, Heroism) — When Played/On Attack: choose a number, then look at
# the top card; if its cost is the chosen number, you may reveal and draw it. P1 plays C-3PO and
# chooses 2 (blindly). The top card SOR_095 (Battlefield Marine) costs 2 → matches → the player is
# offered the card and chooses Draw → SOR_095 is drawn (hand 0→1, deck 3→2).

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
- P1>AnswerDecision:Draw

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DECKCOUNT:2
P1DISCARDCOUNT:0
