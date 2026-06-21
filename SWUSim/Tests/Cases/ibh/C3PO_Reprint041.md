# IBH_041 C-3PO (reprint of IBH_019) — When Played: if Cunning unit, draw. Confirms the duplicate.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_041
WithP1GroundArena: SOR_207:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1NODECISION
