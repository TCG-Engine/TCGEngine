# TWI_095 Pelta Supply Frigate — played with only 1 friendly unit already in play → 2 total including
# itself → Coordinate INACTIVE → no Clone Trooper created.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:TWI_095}
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
