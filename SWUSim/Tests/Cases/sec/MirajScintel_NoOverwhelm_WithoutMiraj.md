# SEC_139 negative guard — without Miraj in play, the same attack has no Overwhelm (no base overflow).

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:6

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1NODECISION
