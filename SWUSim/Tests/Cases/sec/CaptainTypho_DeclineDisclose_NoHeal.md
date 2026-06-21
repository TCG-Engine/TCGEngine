# SEC_098 Captain Typho — On Defense disclose is OPTIONAL. P2 declines the disclose (AnswerDecision:-),
#   so no base heal happens; the base stays at 3 damage. Combat still resolves normally (Typho takes 3,
#   counters 4). Proves the "you may" decline path no-ops cleanly and combat is not blocked.

## GIVEN
CommonSetup: ggw/ggw/{theirBaseDamage:3;theirHandCardIds:SEC_096}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_098:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:4
