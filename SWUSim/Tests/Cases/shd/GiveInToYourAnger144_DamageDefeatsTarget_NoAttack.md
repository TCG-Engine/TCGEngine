# SHD_144 — if the 1 damage defeats the chosen unit, there is nothing left to force an attack with. P1
# plays Give In to Your Anger on P2's SOR_128 (3/1); the 1 damage defeats it outright, so no forced
# attack occurs — P1's unit and base are untouched.

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 1
WithP1Hand: SHD_144
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
