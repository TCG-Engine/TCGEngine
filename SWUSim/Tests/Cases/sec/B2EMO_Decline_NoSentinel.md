# SEC_248 B2EMO — decline the On Attack disclose → no Sentinel granted.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_248:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_148
WithP1Hand: SEC_153

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
P1NODECISION
