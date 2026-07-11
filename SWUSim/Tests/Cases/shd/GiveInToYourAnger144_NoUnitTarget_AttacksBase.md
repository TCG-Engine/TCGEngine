# SHD_144 — "...next action must be an attack action with that unit, if able. It must attack a unit, if
# able." When the compelled unit CAN'T attack a unit (its controller's opponent has no units) but CAN
# attack the base, it must still attack — hitting the base. P1 controls no units; P1 plays Give In to
# Your Anger on P2's SOR_046 (3/7), which is forced to attack and, having no unit to hit, strikes P1's
# base for 3. P2's unit ends exhausted with only the 1 SHD_144 damage (a base deals no counter).

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 1
WithP1Hand: SHD_144
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:3
