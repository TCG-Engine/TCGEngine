# SHD_144 — "It must attack a unit, if able." When the compelled unit has more than one enemy unit it
# could attack, its controller CHOOSES which (but must pick a unit, not the base). P1 has two SOR_046
# (3/7); P1 plays Give In to Your Anger on P2's SOR_046, which is then forced to attack — P2 chooses the
# second of P1's units (idx 1). That unit takes 3; P1's first unit is untouched; P2's unit ends exhausted.

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 1
WithP1Hand: SHD_144
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
