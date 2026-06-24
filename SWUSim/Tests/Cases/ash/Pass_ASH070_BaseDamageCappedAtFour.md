# ASH_070 At Attin Safety Droid (Ground, 1/4, cost 2) — "If your base would be dealt more than 4 damage,
# prevent all but 4 of that damage." P1 controls the Droid; P2's SOR_038 (7 power) attacks P1's base, so
# the 7 combat damage is capped to 4.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_070:1:0
WithP2GroundArena: SOR_038:1:0
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:4
