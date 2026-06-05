# SOR_252 Restock (Event, cost 1) — "Choose up to 4 cards in a discard pile. Put them
# on the bottom of their owner's deck in a random order." P1's discard is seeded with
# three cards; playing Restock adds the event itself to the discard (4 total). Choosing
# the first two seeded cards (SOR_095, SOR_046) sends them to the deck bottom, leaving
# SOR_032 and the spent Restock (2) — with SOR_032 first.

## GIVEN
CommonSetup: ggk/ggk/{myResources:1;handCardIds:SOR_252;discardCardIds:SOR_095,SOR_046,SOR_032}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1

## EXPECT
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:SOR_032
