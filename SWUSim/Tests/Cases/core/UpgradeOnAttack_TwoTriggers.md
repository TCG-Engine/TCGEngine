# SOR_244 (innate OnAttack) + JTL_172 (upgrade OnAttack) -> both enter EffectStack.
# Player resolves JTL_172 first (deal 1 to P2 unit). The lone remaining trigger
# (SOR_244 innate) then auto-dispatches; it has no valid enemy Vehicle target -> no-op.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_244:1:0
WithP1GroundArenaUpgrade: 0:JTL_172
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>ResolveTrigger:OnAttackFromUpgrade:JTL_172
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
