# ASH_070 At Attin Safety Droid — the cap only triggers above 4. P2's SOR_095 (3 power) attacks P1's base
# while the Droid is in play; 3 ≤ 4, so the full 3 lands (the cap does not reduce it).
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_070:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
