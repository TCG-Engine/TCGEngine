# SEC_158 — without a friendly unit defeated while attacking this phase, no cards are drawn. P1 just
#   plays SEC_158 (no combat happened) → hand ends empty.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_158
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
