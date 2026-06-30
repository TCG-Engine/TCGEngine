# SEC_125 Reconnaissance (event, cost 2) — If you control a ground unit AND a space unit, draw 2 cards.
#   P1 controls both → draws 2 (SEC_125 played, 2 drawn → hand 2).

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1SpaceArena: SEC_185:1:0
WithP1Hand: SEC_125
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:2
