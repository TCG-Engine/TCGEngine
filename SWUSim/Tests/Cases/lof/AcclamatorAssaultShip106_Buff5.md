# LOF_106 Acclamator Assault Ship (5/8) — On Attack: may give another unit +5/+5 for this phase. It
# attacks the base and buffs the friendly SOR_095 (3 → 8 power).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_106:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8
