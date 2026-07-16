# WhenDefeated_ExpToUnique
#// LOF_095 Lor San Tekka (3/2) — When Defeated: may give an Experience token to a unique unit. He attacks
#// a 4/7 and dies; P1 gives an Experience token to the unique Plo Koon.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_095:1:0
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
