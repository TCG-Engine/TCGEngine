# LOF_045 Yaddle — On Attack: each other friendly Jedi unit gains Restore 1 for this phase. Yaddle
# attacks the base; her fellow Jedi (Plo Koon) gains Restore.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_045:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Restore
