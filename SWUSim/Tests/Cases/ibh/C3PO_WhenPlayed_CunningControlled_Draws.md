# IBH_019 C-3PO (Ground, 1/4, Command/Heroism, cost 3) — When Played: if you control a Cunning unit,
#   draw a card. P1 controls SOR_207 (Cunning). Playing C-3PO draws 1.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_019
WithP1GroundArena: SOR_207:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:1
P1DECKCOUNT:0
P1NODECISION
