# WhenDefeated_AoEExhaustedGround
#// SEC_263 Assassin Probe (Ground, 4/4) — When Defeated: deal 1 to each exhausted enemy ground unit.
#//   SEC_263 attacks LAW_124 (idx0) and dies → the two exhausted enemies take 1 each; the ready SOR_095 is untouched.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_263:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:2:DAMAGE:0
P1NODECISION
