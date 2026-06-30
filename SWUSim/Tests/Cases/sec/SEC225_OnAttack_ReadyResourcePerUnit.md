# SEC_225 Synara San (Ground, 7/7) — Hidden + On Attack: for each friendly unit, ready a friendly
#   resource. With 2 friendly units (SEC_225 + SEC_041) and 2 exhausted resources, attacking readies 2.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_225:1:0
WithP1GroundArena: SEC_041:1:0
WithP1Resources: 2:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:7
P1RESAVAILABLE:2
