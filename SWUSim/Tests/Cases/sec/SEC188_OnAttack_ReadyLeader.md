# SEC_188 Darth Traya (Ground, 2/5) — On Attack: you may ready a non-unit (undeployed) leader. P1's
#   leader starts exhausted; SEC_188 attacks P2's base and readies it.

## GIVEN
CommonSetup: yyk/rrk/{myLeaderReady:0}
WithActivePlayer: 1
WithP1GroundArena: SEC_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1LEADER:READY
