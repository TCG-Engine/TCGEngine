# SEC_115 Taylander Shuttle — no initiative → no Spy token. P1OnlyActions gives P2 the initiative.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1SpaceArena: SEC_115:1:0

## WHEN
- P1>AttackSpaceArena:0

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:0
P1NODECISION
