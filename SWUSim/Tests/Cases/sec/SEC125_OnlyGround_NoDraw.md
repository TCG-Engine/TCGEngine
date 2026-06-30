# SEC_125 — without both a ground AND a space unit, no cards are drawn. P1 controls only a ground unit,
#   so playing SEC_125 just sends it to discard (hand ends empty).

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_125
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
