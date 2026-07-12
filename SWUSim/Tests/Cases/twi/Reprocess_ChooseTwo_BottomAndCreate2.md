# TWI_088 Reprocess (Event, cost 3, Command/Villainy) — "Choose up to 4 units in your discard pile.
# Put them on the bottom of your deck in a random order and create that many Battle Droid tokens."
# Discard seeded with 2 units (SEC_080, JTL_069); choose both via MZMULTICHOOSE. Both go to the deck
# bottom (deck 2 → 4), leaving only the Reprocess event in discard, and 2 Battle Droids are created.

## GIVEN
CommonSetup: gyk/grw/{myResources:3;handCardIds:TWI_088;discardCardIds:SEC_080}
P1OnlyActions: true
WithP1Discard: JTL_069
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1DISCARDCOUNT:1
P1DECKCOUNT:4
