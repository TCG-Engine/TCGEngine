# IBH_019 C-3PO — When Played with NO Cunning unit controlled (C-3PO is Command/Heroism): no draw.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_019
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P1NODECISION
