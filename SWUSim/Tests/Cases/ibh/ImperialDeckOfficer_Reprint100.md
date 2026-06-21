# IBH_100 Imperial Deck Officer (reprint of IBH_062) — Action [Exhaust]: heal 2 from a Villainy unit.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: IBH_100:1:0
WithP1GroundArena: SEC_080:1:3

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED
