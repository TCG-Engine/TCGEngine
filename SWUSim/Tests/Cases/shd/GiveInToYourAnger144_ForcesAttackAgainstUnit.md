# SHD_144 Give In to Your Anger (Event, cost 1, Villainy/Aggression) — "Deal 1 damage to an enemy unit.
# Its controller's next action this phase must be an attack action with that unit, if able. It must
# attack a unit, if able." P1 plays it targeting P2's SOR_046 (3/7); that unit takes 1, then on P2's
# forced next action it attacks P1's only unit (SOR_046 3/7) — NOT P1's base. Single friendly unit, so
# the attack auto-resolves. P2's unit ends exhausted with 1+3=4 damage (SHD_144 + 3 counter); P1's unit
# takes 3; P1's base is untouched (the compulsion forces a unit attack, not a base attack).

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 1
WithP1Hand: SHD_144
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:0
