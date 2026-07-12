# TWI_088 Reprocess — no units in the discard pile → the effect fizzles cleanly: no MZMULTICHOOSE,
# no tokens created ("that many" = 0). Only the Reprocess event sits in discard afterward.

## GIVEN
CommonSetup: gyk/grw/{myResources:3;handCardIds:TWI_088}
P1OnlyActions: true
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DECKCOUNT:1
P1NODECISION
