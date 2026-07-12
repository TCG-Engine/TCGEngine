# TWI_252 Aggrieved Parliamentarian (Unit, Ground) — "When Played: Choose an opponent. They shuffle their
# discard pile and put it on the bottom of their deck." P2's 1-card discard moves to the bottom of their deck.
## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_252}
P1OnlyActions: true
WithP2Discard: SOR_095
WithP2Deck: [SOR_046]
## WHEN
- P1>PlayHand:0
## EXPECT
P2DISCARDCOUNT:0
P2DECKCOUNT:2
