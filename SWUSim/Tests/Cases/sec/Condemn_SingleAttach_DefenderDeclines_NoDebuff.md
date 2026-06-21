# SEC_038 Condemn — the granted disclose is "may". P2 (defending player) declines (AnswerDecision:-),
#   so no -6/-0 is applied: SEC_118 deals its full 6 to the base. Proves the decline path no-ops.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1GroundArena: SEC_118:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:-

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:POWER:6
