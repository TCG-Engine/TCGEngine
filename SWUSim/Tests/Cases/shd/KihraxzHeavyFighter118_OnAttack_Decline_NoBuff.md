# SHD_118 Kihraxz Heavy Fighter — declining the optional exhaust means no +3: the base attack deals the
# printed 3, and SOR_095 stays ready.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1SpaceArena: SHD_118:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
