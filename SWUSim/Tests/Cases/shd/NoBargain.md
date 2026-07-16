# OppDiscardsAndDraw
#// SHD_244 No Bargain (3-cost event, Villainy) — "Each opponent discards a card from their hand. Draw a
#// card." P2 (hand of exactly 1) auto-discards SOR_095; P1 draws a card.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;theirhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Hand: SHD_244
WithP1Deck: [SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1HANDCOUNT:1
