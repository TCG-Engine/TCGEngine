# WhenDefeated_ExpRebels
#// SEC_252 Maarva Andor (Ground, 3/4, Heroism) — When Defeated: give an Experience token to each friendly
#//   Rebel unit. Maarva (idx1) attacks LAW_124 and dies → the friendly Rebel SOR_095 gets +1/+1.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_252:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
