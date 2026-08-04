# VISUAL CHECK — zone slide, hand -> arena (play a unit)
#
# Visual-only schema.
#
# What to look at:
#   • The Dark Trooper flies from the hand into the ground arena and scales to arena card size.
#   • It is not visible in BOTH places at once — the destination stays hidden until the clone lands.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;myhandCardIds:SEC_080}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
