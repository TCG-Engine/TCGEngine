# VISUAL CHECK — zone slide, hand -> arena (play a unit)
#
# Visual-only schema.
#
# What to look at:
#   • The Dark Trooper flies from the hand into the ground arena and scales to arena card size.
#   • It is not visible in BOTH places at once — the destination stays hidden until the clone lands.
#   • A played unit enters EXHAUSTED, so the card travels ALREADY TILTED (9°, the RotationRules angle)
#     and already dimmed — it must NOT fly upright and then snap into the tilt on landing.
#   • Its landed pose matches the slot exactly: no over/undershoot, no swing around a corner.
#   • Contrast partner: ZoneMove_PlayUnit_EntersReady.md, where the arrival stays upright.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;myhandCardIds:SEC_080}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
