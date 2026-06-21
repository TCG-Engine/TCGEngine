# SEC_208 Hunter — against a READY defender there's no -4/-0, so Hunter takes the full counter (3).

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_208:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P1NODECISION
