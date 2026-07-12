# TWI_100 Petition the Senate — with only two Official units (< 3), no cards are drawn.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:TWI_100}
P1OnlyActions: true
WithP1GroundArena: [TWI_056:1:0 TWI_157:1:0]
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:4
