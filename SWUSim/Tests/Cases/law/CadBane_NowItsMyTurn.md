# OnAttackDefeatCreditsExp
#// LAW_032 Cad Bane (6/6, Shielded, Overwhelm) — On Attack: defeat any number of friendly Credit tokens;
#// give an Experience token to this unit for each. Defeat 2 Credits -> 2 Experience (6/6 -> 8/8).

## GIVEN
CommonSetup: brk/bgw/{myResources:0}
P1OnlyActions: true
WithP1Credits: 2
WithP1GroundArena: LAW_032:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_032
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:8
P1CREDITCOUNT:0
