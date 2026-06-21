# LAW_091 Val (2/4) — When Defeated: give a Shield token to an enemy unit. Pre-damaged Val (2) attacks
# SOR_046 and dies to the counter; the enemy SOR_046 gains a Shield.

## GIVEN
CommonSetup: byk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_091:1:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
