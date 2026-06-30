# SEC_145 Confidence in Victory — no win when the opponent ALSO controls units in the chosen arena.
# P1 plays it (Ground) but P2 also has a ground unit, so at regroup P1 is NOT the only player with units
# there → no win. The game continues: after passing into the next action phase, P1's attack still lands
# (proving the game did not end).

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 1
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
