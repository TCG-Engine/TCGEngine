# WhenDefeated_PalpatineExp
#// SEC_027 The Chancellor's Shuttle (Ground, 1/3) — Restore 1 + When Defeated: if you control Chancellor
#//   Palpatine (leader or unit), you may give an Experience token to a unit. SEC_082 (Palpatine unit) is
#//   in play; SEC_027 attacks LAW_124 and dies → give an Experience token to SEC_082.

## GIVEN
CommonSetup: bbk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_082:1:0
WithP1GroundArena: SEC_027:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_082
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
