# ResourceEventAndTopDeck
#// LAW_171 Stockpile (Command event, cost 6) — "Resource this event and the top card of your deck."
#// Paying 6 exhausts P1's 6 resources; then the event itself + the top deck card become resources
#// (exhausted). Net: 8 resources (all exhausted), deck -1, event NOT in discard.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1Deck: SOR_237
WithP1Hand: LAW_171

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:8
P1RESAVAILABLE:0
P1DECKCOUNT:0
P1DISCARDCOUNT:0
