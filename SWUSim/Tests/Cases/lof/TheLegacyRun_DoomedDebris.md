# WhenDefeated_SplitDamage
#// LOF_213 The Legacy Run (3/3) — When Defeated: deal 6 damage divided as you choose among enemy units. It
#// attacks a 4/7, dies to the counter, and assigns all 6 to the surviving enemy 3/7.

## GIVEN
CommonSetup: yyk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_213:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1:6

## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:6
