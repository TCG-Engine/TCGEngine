# SEC_041 (Ground, 1/4) — When an enemy unit deals combat damage to your base: this unit gains
#   Sentinel for this phase. P1 has SEC_041; P2's SOR_046 (3 power) attacks P1's base → SEC_041
#   reacts and gains Sentinel.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
