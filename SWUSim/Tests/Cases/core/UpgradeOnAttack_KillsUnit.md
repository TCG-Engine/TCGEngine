# SOR_137 on Yoda (Force unit): OnAttack deals 1 damage — kills P2 unit at 1 HP remaining.
# Regression test for SWUDealDamageToUnit premature removed=true bug.
# Yoda (SOR_045, 3/4, Force) with Fallen Lightsaber (SOR_137) attacks P2's Battlefield Marine
# (SOR_095, 3/3) which has 2 pre-damage (1 HP left). OnAttack fires, deals 1 to each P2 ground
# unit — Marine reaches 3 damage >= 3 HP and must be defeated and moved to P2's discard pile.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_045:1:0
WithP1GroundArenaUpgrade: 0:SOR_137
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
