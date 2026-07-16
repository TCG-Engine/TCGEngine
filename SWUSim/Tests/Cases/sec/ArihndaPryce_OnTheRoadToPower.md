# WhenDefeated_SacForBaseDamage
#// SEC_136 Arihnda Pryce (Ground, 4/4) — When Defeated: you may defeat another friendly unit; if you do,
#//   deal 4 to each enemy base. SEC_136 (idx1) attacks LAW_124 and dies → defeat SOR_095 → 4 to P2 base.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_136:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:4
P1NODECISION
