# WhenDefeatedExpRebel
#// LAW_142 Scarif Lieutenant (2/1) — When Defeated: give an Experience token to a friendly Rebel unit.
#// Attacks SOR_046 and dies; SOR_095 (Rebel) gets the Experience.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_142:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
