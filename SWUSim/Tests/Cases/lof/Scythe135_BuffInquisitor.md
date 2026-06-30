# LOF_135 Scythe — On Attack: may give another friendly Inquisitor unit +2/+0 for this phase. Scythe
# attacks the base and buffs the friendly Inquisitor (Eighth Brother, 5 → 7 power).

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1SpaceArena: LOF_135:1:0
WithP1GroundArena: LOF_087:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
