# SHD_033 Synara San — the Bounty exists ONLY while she is exhausted. A READY Synara defeated in
# combat offers no bounty: no decision pending, no base damage. (Absence guard for the conditional.)

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SHD_033:1:2    # ready, 2 damage

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1NODECISION
