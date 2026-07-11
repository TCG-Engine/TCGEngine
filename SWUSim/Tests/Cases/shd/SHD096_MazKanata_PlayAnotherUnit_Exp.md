# SHD_096 Maz Kanata — "When you play another unit: Give an Experience token to this unit." Maz (1/1) is
# in play; playing another unit (SOR_095) gives Maz an Experience token → 2/2.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SHD_096:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
