# LOF_194 J-Type Nubian Starship — When Played: draw a card. P1 plays it and draws.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3;handCardIds:LOF_194}
P1OnlyActions: true
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
