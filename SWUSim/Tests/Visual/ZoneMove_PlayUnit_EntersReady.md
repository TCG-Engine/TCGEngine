# VISUAL CHECK — zone slide, hand -> arena for a unit that enters play READY
#
# Visual-only schema. Contrast partner to ZoneMove_PlayUnit.md.
#
# SOR_193 Millennium Falcon reads "This unit enters play ready", so unlike a normal play it lands
# UPRIGHT and undimmed. The slide must therefore carry NO tilt and NO exhaust tint — the clone
# adopts whatever pose the destination actually ends in, so a ready arrival looks exactly like the
# pre-tilt behaviour did.
#
# What to look at:
#   • The Falcon flies from the hand into the SPACE arena and scales to arena card size.
#   • It stays upright and full-brightness for the whole flight, and does not tilt on landing.
#   • Play ZoneMove_PlayUnit.md right after this one: that unit should tilt for the whole flight.

## GIVEN
CommonSetup: bbk/bbk/{myResources:12;myhandCardIds:SOR_193}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
