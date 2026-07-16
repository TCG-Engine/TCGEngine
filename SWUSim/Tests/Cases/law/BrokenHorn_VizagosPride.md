# WhenPlayedDrawAndResource
#// LAW_083 Broken Horn (5/4) — When Played: if you have fewer cards in hand than an opponent, draw a
#// card; if you control fewer resources than an opponent, resource the top card of your deck. P1 ends
#// with fewer of both (hand 0 vs 3, resources 5 vs 6) -> draw 1 AND resource 1.

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:6}
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP2Hand: SEC_080
WithP1Deck: SOR_237
WithP1Deck: SOR_095
WithP1Hand: LAW_083

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESCOUNT:6
P1DECKCOUNT:0
