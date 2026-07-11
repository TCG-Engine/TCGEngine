# SHD_144 — "...must be an attack action with that unit, if able." The compulsion is conditional on the
# unit being ABLE to attack. Here P2's SOR_046 is already exhausted, so it cannot attack: it still takes
# the 1 damage, but no forced attack happens (the compulsion lapses) — P1's unit and base are untouched.

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 1
WithP1Hand: SHD_144
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
