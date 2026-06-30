# IBH_062 Imperial Deck Officer (Ground, 1/4, Vigilance) — Action [Exhaust]: heal 2 damage from a
#   Villainy unit. The friendly Villainy unit SEC_080 (3 damage) heals to 1; the Deck Officer (Vigilance)
#   is not a valid target, so SEC_080 auto-resolves. Deck Officer exhausts.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: IBH_062:1:0
WithP1GroundArena: SEC_080:1:3

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED
