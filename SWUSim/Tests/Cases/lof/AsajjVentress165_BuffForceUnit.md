# LOF_165 Asajj Ventress — When Played/On Attack: give another friendly Force unit +2/+0 for this phase.
# On attack she buffs Plo Koon (6 → 8 power).

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_165:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:POWER:8
