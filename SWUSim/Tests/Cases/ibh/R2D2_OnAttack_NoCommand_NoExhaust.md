# IBH_011 R2-D2 — On Attack with NO Command unit controlled: the exhaust does not happen (R2-D2 itself
#   is Cunning/Heroism, not Command). The cost-2 enemy stays ready and no decision is presented.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: IBH_011:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2BASEDMG:1
P1NODECISION
