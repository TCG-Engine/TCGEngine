# TWI_035 Morgan Elsbeth — the "may" is optional: declining (AnswerDecision:-) keeps the friendly unit
# and draws nothing.

## GIVEN
CommonSetup: bbk/rrw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_035:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:0
